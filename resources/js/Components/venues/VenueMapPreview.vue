<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    latitude: {
        type: [Number, String, null],
        default: null,
    },
    longitude: {
        type: [Number, String, null],
        default: null,
    },
    addressQuery: {
        type: String,
        default: '',
    },
    zoom: {
        type: Number,
        default: 15,
    },
});

const DARK_MAP_STYLES = [
    { elementType: 'geometry', stylers: [{ color: '#0f172a' }] },
    { elementType: 'labels.text.fill', stylers: [{ color: '#94a3b8' }] },
    { elementType: 'labels.text.stroke', stylers: [{ color: '#0b1220' }] },
    { featureType: 'administrative', elementType: 'geometry', stylers: [{ color: '#334155' }] },
    { featureType: 'poi', elementType: 'labels.text.fill', stylers: [{ color: '#64748b' }] },
    { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#1e293b' }] },
    { featureType: 'road', elementType: 'geometry.stroke', stylers: [{ color: '#0f172a' }] },
    { featureType: 'road', elementType: 'labels.text.fill', stylers: [{ color: '#94a3b8' }] },
    { featureType: 'transit', elementType: 'geometry', stylers: [{ color: '#1e293b' }] },
    { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#0a2536' }] },
];

const DEFAULT_CENTER = { lat: -14.235, lng: -51.9253 };

const mapEl = ref(null);
const mapsJsError = ref('');

const mapsApiKey = String(import.meta.env.VITE_GOOGLE_MAPS_API_KEY || '').trim();
const mapsApiKeyConfigured = mapsApiKey.length > 0;
let googleMapsLoaderPromise = null;
let mapsScriptPromise = null;
let mapInstance = null;
let markerInstance = null;
let geocoderInstance = null;
let themeObserver = null;
let geocodeRequestId = 0;

const normalizeCoordinate = (value) => {
    const normalized = typeof value === 'string' ? value.replace(',', '.').trim() : value;
    const parsed = Number(normalized);
    return Number.isFinite(parsed) ? parsed : null;
};

const targetPosition = computed(() => {
    const lat = normalizeCoordinate(props.latitude);
    const lng = normalizeCoordinate(props.longitude);

    if (lat !== null && lng !== null) {
        return { lat, lng };
    }

    return null;
});

const normalizedAddress = computed(() => String(props.addressQuery || '').trim());

const fallbackEmbedUrl = computed(() => {
    if (targetPosition.value) {
        return `https://maps.google.com/maps?q=${targetPosition.value.lat},${targetPosition.value.lng}&z=15&output=embed`;
    }

    if (normalizedAddress.value) {
        return `https://maps.google.com/maps?q=${encodeURIComponent(normalizedAddress.value)}&z=14&output=embed`;
    }

    return `https://maps.google.com/maps?q=${DEFAULT_CENTER.lat},${DEFAULT_CENTER.lng}&z=4&output=embed`;
});

const showJsMap = computed(() => mapsApiKeyConfigured && !mapsJsError.value);
const showIframeFallback = computed(() => !!fallbackEmbedUrl.value && (!mapsApiKeyConfigured || !!mapsJsError.value));

const isDarkMode = () => typeof document !== 'undefined'
    && (
        document.documentElement.classList.contains('app-dark')
        || document.documentElement.classList.contains('dark')
    );

const getMapThemeOptions = () => {
    const dark = isDarkMode();
    const colorSchemeApi = window.google?.maps?.ColorScheme;

    if (colorSchemeApi) {
        return {
            colorScheme: dark ? colorSchemeApi.DARK : colorSchemeApi.LIGHT,
            ...(dark ? { backgroundColor: '#0b1220' } : {}),
        };
    }

    if (dark) {
        return {
            styles: DARK_MAP_STYLES,
            backgroundColor: '#0b1220',
        };
    }

    return {
        styles: null,
        backgroundColor: null,
    };
};

const applyMapTheme = () => {
    if (!mapInstance || !window.google?.maps) {
        return;
    }

    mapInstance.setOptions(getMapThemeOptions());
};

const setMarkerPosition = (position) => {
    if (!mapInstance || !window.google?.maps?.Marker) {
        return;
    }

    if (!markerInstance) {
        markerInstance = new window.google.maps.Marker({
            map: mapInstance,
            position,
        });
        return;
    }

    markerInstance.setPosition(position);
};

const clearMarker = () => {
    if (!markerInstance) {
        return;
    }

    markerInstance.setMap(null);
    markerInstance = null;
};

const loadGoogleMaps = async () => {
    if (window.google?.maps) {
        return true;
    }

    if (!mapsApiKeyConfigured) {
        return false;
    }

    if (!mapsScriptPromise) {
        mapsScriptPromise = new Promise((resolve, reject) => {
            const existingScript = document.querySelector('script[data-google-maps-preview="1"]');
            if (existingScript) {
                existingScript.addEventListener('load', resolve, { once: true });
                existingScript.addEventListener('error', reject, { once: true });
                return;
            }

            const script = document.createElement('script');
            script.dataset.googleMapsPreview = '1';
            script.src = `https://maps.googleapis.com/maps/api/js?key=${mapsApiKey}&libraries=marker&loading=async`;
            script.async = true;
            script.defer = true;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    if (!googleMapsLoaderPromise) {
        googleMapsLoaderPromise = mapsScriptPromise
            .then(() => true)
            .catch((error) => {
                mapsScriptPromise = null;
                googleMapsLoaderPromise = null;
                throw error;
            });
    }

    await googleMapsLoaderPromise;
    return !!window.google?.maps;
};

const ensureMapInitialized = async () => {
    if (!mapsApiKeyConfigured) {
        return false;
    }

    mapsJsError.value = '';

    try {
        const loaded = await loadGoogleMaps();
        if (!loaded || !mapEl.value || !window.google?.maps) {
            return false;
        }

        if (!geocoderInstance) {
            geocoderInstance = new window.google.maps.Geocoder();
        }

        if (!mapInstance) {
            mapInstance = new window.google.maps.Map(mapEl.value, {
                center: targetPosition.value || DEFAULT_CENTER,
                zoom: targetPosition.value ? props.zoom : 4,
                ...getMapThemeOptions(),
            });
        }

        applyMapTheme();
        return true;
    } catch (error) {
        console.error('Falha ao inicializar preview do mapa:', error);
        mapsJsError.value = 'Falha ao carregar o mapa.';
        return false;
    }
};

const renderTargetOnMap = async () => {
    const initialized = await ensureMapInitialized();
    if (!initialized) {
        return;
    }

    if (!mapInstance) {
        return;
    }

    if (targetPosition.value) {
        mapInstance.setCenter(targetPosition.value);
        mapInstance.setZoom(props.zoom);
        setMarkerPosition(targetPosition.value);
        return;
    }

    if (!normalizedAddress.value) {
        clearMarker();
        mapInstance.setCenter(DEFAULT_CENTER);
        mapInstance.setZoom(4);
        return;
    }

    const requestId = ++geocodeRequestId;
    geocoderInstance.geocode({ address: normalizedAddress.value }, (results, status) => {
        if (requestId !== geocodeRequestId) {
            return;
        }

        if (status !== 'OK' || !results?.length) {
            return;
        }

        const location = results[0].geometry?.location;
        if (!location) {
            return;
        }

        mapInstance.setCenter(location);
        mapInstance.setZoom(Math.max(14, props.zoom));
        setMarkerPosition(location);
    });
};

watch(
    () => [props.latitude, props.longitude, props.addressQuery],
    () => {
        renderTargetOnMap();
    },
    { immediate: true },
);

onMounted(() => {
    renderTargetOnMap();

    if (typeof MutationObserver !== 'undefined' && typeof document !== 'undefined') {
        themeObserver = new MutationObserver(() => {
            applyMapTheme();
        });

        themeObserver.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class'],
        });
    }
});

onUnmounted(() => {
    if (themeObserver) {
        themeObserver.disconnect();
        themeObserver = null;
    }

    clearMarker();
    mapInstance = null;
    geocoderInstance = null;
});
</script>

<template>
    <div class="relative w-full">
        <div v-if="showJsMap" ref="mapEl" class="h-full w-full" />
        <iframe
            v-else-if="showIframeFallback"
            :src="fallbackEmbedUrl"
            class="h-full w-full"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
        />
    </div>
</template>
