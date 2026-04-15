<script setup>
import { computed, nextTick, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { importLibrary as importGoogleLibrary, setOptions } from '@googlemaps/js-api-loader';
import 'iconify-icon';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFilterBar from '@/Components/ui/BoFilterBar.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoDataTableEmpty from '@/Components/ui/BoDataTableEmpty.vue';
import BoConfirmButton from '@/Components/ui/BoConfirmButton.vue';

defineOptions({ layout: AppLayout });
const props = defineProps({ venues: Object, sizes: Array, types: Array, filters: Object, mapPoints: Array });

const viewMode = ref('list');
const mapEl = ref(null);
const mapReady = ref(false);
const mapError = ref('');
const mapNotice = ref('');
let googleMapsLoaderPromise = null;
let mapInstance = null;
let activeInfoWindow = null;
let themeObserver = null;
const legacyMarkerIconCache = new Map();
const renderedMarkerHandles = new Map();
const hoveredVenueId = ref(null);

const DEFAULT_MARKER_ICON = 'mdi:map-marker';
const DEFAULT_MARKER_COLOR = '#4f46e5';
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

const localFilters = reactive({
    venue_type_id: props.filters?.venue_type_id ?? null,
    status: props.filters?.status ?? null,
    city: props.filters?.city ?? '',
    has_performed: props.filters?.has_performed ?? null,
    rating: props.filters?.rating ?? null,
    search: props.filters?.search ?? '',
});

const syncLocalFiltersFromProps = () => {
    localFilters.venue_type_id = props.filters?.venue_type_id ?? null;
    localFilters.status = props.filters?.status ?? null;
    localFilters.city = props.filters?.city ?? '';
    localFilters.has_performed = props.filters?.has_performed ?? null;
    localFilters.rating = props.filters?.rating ?? null;
    localFilters.search = props.filters?.search ?? '';
};

const filterChips = computed(() => {
    const chips = [];
    if (localFilters.search) chips.push({ key: 'search', label: localFilters.search });
    if (localFilters.venue_type_id) {
        const type = props.types?.find((s) => s.id === localFilters.venue_type_id);
        if (type) chips.push({ key: 'venue_type_id', label: type.name });
    }
    if (localFilters.status) chips.push({ key: 'status', label: localFilters.status });
    if (localFilters.city) chips.push({ key: 'city', label: localFilters.city });
    if (localFilters.has_performed !== null) chips.push({ key: 'has_performed', label: localFilters.has_performed ? 'Já tocou' : 'Nunca tocou' });
    if (localFilters.rating) chips.push({ key: 'rating', label: `${localFilters.rating} estrelas` });
    return chips;
});

const submitFilters = () => {
    router.get(route('venues.index'), localFilters, { preserveState: true, preserveScroll: true, replace: true });
};

const resetFilters = () => {
    localFilters.venue_type_id = null;
    localFilters.status = null;
    localFilters.city = '';
    localFilters.has_performed = null;
    localFilters.rating = null;
    localFilters.search = '';
    submitFilters();
};

const removeChip = (key) => {
    localFilters[key] = ['search', 'city'].includes(key) ? '' : null;
    submitFilters();
};

const cancelFilters = () => {
    syncLocalFiltersFromProps();
};

watch(() => props.filters, syncLocalFiltersFromProps, { deep: true });

const paginate = (event) => {
    router.get(route('venues.index'), { ...localFilters, page: event.page + 1 }, { preserveState: true, preserveScroll: true, replace: true });
};

const removeVenue = (id) => router.delete(route('venues.destroy', id), { preserveScroll: true });

const statusOptions = [
    { label: 'Indefinido', value: 'undefined' },
    { label: 'Não relevante', value: 'not_relevant' },
    { label: 'Contatado', value: 'contacted' },
    { label: 'Vetado', value: 'vetoed' },
    { label: 'Em negociação', value: 'negotiating' },
    { label: 'Portas abertas', value: 'open_doors' },
];

const statusLabels = {
    undefined: 'Indefinido',
    not_relevant: 'Não relevante',
    contacted: 'Contatado',
    vetoed: 'Vetado',
    negotiating: 'Em negociação',
    open_doors: 'Portas abertas',
};

const statusColors = {
    undefined: '#94a3b8',
    not_relevant: '#6b7280',
    contacted: '#3b82f6',
    vetoed: '#dc2626',
    negotiating: '#f59e0b',
    open_doors: '#16a34a',
};

const escapeHtml = (value = '') => String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

const sanitizeIcon = (icon) => (/^[a-z0-9-]+:[a-z0-9-]+$/i.test(icon || '') ? icon : DEFAULT_MARKER_ICON);
const sanitizeColor = (color) => (/^#[0-9a-fA-F]{6}$/.test(color || '') ? color : DEFAULT_MARKER_COLOR);
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

const toIconSvgUrl = (icon, color = '#ffffff') => {
    const safeIcon = sanitizeIcon(icon);
    const safeColor = encodeURIComponent(color.replace('#', ''));
    return `https://api.iconify.design/${encodeURIComponent(safeIcon)}.svg?color=%23${safeColor}`;
};

const formatRating = (rating) => {
    const normalized = Math.max(0, Math.min(5, Number(rating) || 0));
    if (!normalized) {
        return 'Sem avaliação';
    }

    return `${'★'.repeat(normalized)}${'☆'.repeat(5 - normalized)} (${normalized}/5)`;
};

const formatRatingStars = (rating) => {
    const normalized = Math.max(0, Math.min(5, Number(rating) || 0));
    return `${'★'.repeat(normalized)}${'☆'.repeat(5 - normalized)}`;
};

const normalizedMapPoints = computed(() => (props.mapPoints || []).map((point) => {
    const lat = Number(point?.lat);
    const lng = Number(point?.lng);

    return {
        ...point,
        lat,
        lng,
        isValid: Number.isFinite(lat) && Number.isFinite(lng),
    };
}).filter((point) => point.isValid));

const buildInfoWindowContent = (point) => {
    const icon = sanitizeIcon(point?.type?.icon);
    const color = sanitizeColor(point?.type?.color);
    const typeName = point?.type?.name || 'Tipo não informado';
    const address = point?.address || 'Endereço não informado';

    return `
        <div style="min-width:260px;padding:4px 2px;font-family:inherit;">
            <p style="margin:0 0 8px 0;font-size:14px;font-weight:700;color:#0f172a;">${escapeHtml(point?.name || '')}</p>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;color:#334155;">
                <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:9999px;background:${escapeHtml(color)};color:#fff;">
                    <img src="${toIconSvgUrl(icon)}" alt="ícone" width="18" height="18" style="display:block;" />
                </span>
                <span style="font-size:13px;">${escapeHtml(typeName)}</span>
            </div>
            <p style="margin:0 0 6px 0;font-size:12px;color:#475569;">Avaliação: ${escapeHtml(formatRating(point?.rating))}</p>
            <p style="margin:0;font-size:12px;color:#64748b;line-height:1.4;">${escapeHtml(address)}</p>
        </div>
    `;
};

const buildPinElement = (point, PinElement) => {
    const iconify = document.createElement('iconify-icon');
    iconify.setAttribute('icon', sanitizeIcon(point?.type?.icon));
    iconify.style.fontSize = '22px';
    iconify.style.color = '#ffffff';
    iconify.style.display = 'block';

    const pin = new PinElement({
        background: sanitizeColor(point?.type?.color),
        borderColor: '#ffffff',
        glyph: iconify,
        scale: 1.1,
    });

    pin.element.style.filter = 'drop-shadow(0 4px 8px rgba(15, 23, 42, 0.35))';
    return pin;
};

const parseViewBox = (value = '') => {
    const parts = value.trim().split(/\s+/).map(Number);
    if (parts.length !== 4 || parts.some((item) => Number.isNaN(item))) {
        return [0, 0, 24, 24];
    }

    return parts;
};

const getIconSvgPayload = async (icon) => {
    const safeIcon = sanitizeIcon(icon);
    const cacheKey = `payload:${safeIcon}`;
    if (legacyMarkerIconCache.has(cacheKey)) {
        return legacyMarkerIconCache.get(cacheKey);
    }

    try {
        const response = await fetch(toIconSvgUrl(safeIcon));
        if (!response.ok) {
            throw new Error(`Iconify status ${response.status}`);
        }

        const svgText = await response.text();
        const viewBoxMatch = svgText.match(/viewBox=["']([^"']+)["']/i);
        const viewBox = parseViewBox(viewBoxMatch?.[1]);
        const body = svgText
            .replace(/^<svg[^>]*>/i, '')
            .replace(/<\/svg>\s*$/i, '');
        const payload = { body, viewBox };
        legacyMarkerIconCache.set(cacheKey, payload);
        return payload;
    } catch (error) {
        console.warn('Falha ao carregar ícone do marcador legado:', error);
        const fallback = { body: '', viewBox: [0, 0, 24, 24] };
        legacyMarkerIconCache.set(cacheKey, fallback);
        return fallback;
    }
};

const buildLegacyMarkerSvg = (color, iconPayload) => {
    const [, , iconWidth, iconHeight] = iconPayload.viewBox;
    const targetSize = 14;
    const iconX = 20 - (targetSize / 2);
    const iconY = 15 - (targetSize / 2);
    const scaleX = targetSize / (iconWidth || 24);
    const scaleY = targetSize / (iconHeight || 24);

    return `
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="52" viewBox="0 0 40 52">
            <defs>
                <filter id="pinShadow" x="-30%" y="-20%" width="160%" height="180%">
                    <feDropShadow dx="0" dy="3" stdDeviation="2" flood-color="#0f172a" flood-opacity="0.35"/>
                </filter>
            </defs>
            <path d="M20 2C12.82 2 7 7.82 7 15c0 8.25 10.18 18.73 12.06 20.59a1.33 1.33 0 0 0 1.88 0C22.82 33.73 33 23.25 33 15 33 7.82 27.18 2 20 2z" fill="${color}" stroke="#ffffff" stroke-width="2" filter="url(#pinShadow)"/>
            <circle cx="20" cy="15" r="9" fill="rgba(255,255,255,0.15)"/>
            <g transform="translate(${iconX} ${iconY}) scale(${scaleX} ${scaleY})" fill="#ffffff" color="#ffffff">
                ${iconPayload.body}
            </g>
        </svg>
    `.trim();
};

const buildLegacyMarkerIcon = async (point, variant = 'default') => {
    const icon = sanitizeIcon(point?.type?.icon);
    const color = sanitizeColor(point?.type?.color);
    const cacheKey = `marker:${icon}:${color}:${variant}`;

    const isHovered = variant === 'hover';
    const markerWidth = isHovered ? 44 : 40;
    const markerHeight = isHovered ? 57 : 52;
    const markerAnchorX = isHovered ? 22 : 20;
    const markerAnchorY = isHovered ? 42 : 38;

    if (legacyMarkerIconCache.has(cacheKey)) {
        return legacyMarkerIconCache.get(cacheKey);
    }

    const payload = await getIconSvgPayload(icon);
    const svg = buildLegacyMarkerSvg(color, payload);
    const markerIcon = {
        url: `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`,
        scaledSize: new window.google.maps.Size(markerWidth, markerHeight),
        anchor: new window.google.maps.Point(markerAnchorX, markerAnchorY),
    };
    legacyMarkerIconCache.set(cacheKey, markerIcon);
    return markerIcon;
};

const setAdvancedMarkerHovered = (handle, isHovered) => {
    if (!handle?.element) {
        return;
    }

    handle.element.style.transformOrigin = 'center bottom';
    handle.element.style.willChange = 'transform';

    if (isHovered) {
        handle.element.style.transform = 'scale(1.12)';
        handle.element.style.animation = 'bo-marker-bounce 720ms ease-in-out infinite';
        handle.marker.zIndex = 9999;
        return;
    }

    handle.element.style.transform = '';
    handle.element.style.animation = '';
    handle.marker.zIndex = null;
};

const setLegacyMarkerHovered = (handle, isHovered) => {
    if (!handle?.marker) {
        return;
    }

    if (isHovered) {
        handle.marker.setIcon(handle.hoverIcon);
        handle.marker.setAnimation(window.google.maps.Animation.BOUNCE);
        handle.marker.setZIndex(9999);
        return;
    }

    handle.marker.setIcon(handle.baseIcon);
    handle.marker.setAnimation(null);
    handle.marker.setZIndex(null);
};

const setMarkerHovered = (venueId, isHovered) => {
    const handle = renderedMarkerHandles.get(venueId);
    if (!handle) {
        return;
    }

    if (handle.kind === 'advanced') {
        setAdvancedMarkerHovered(handle, isHovered);
        return;
    }

    setLegacyMarkerHovered(handle, isHovered);
};

const onVenueCardHover = (venueId) => {
    if (hoveredVenueId.value && hoveredVenueId.value !== venueId) {
        setMarkerHovered(hoveredVenueId.value, false);
    }

    hoveredVenueId.value = venueId;
    setMarkerHovered(venueId, true);
};

const onVenueCardLeave = (venueId) => {
    if (hoveredVenueId.value !== venueId) {
        return;
    }

    setMarkerHovered(venueId, false);
    hoveredVenueId.value = null;
};

const loadGoogleMaps = async () => {
    if (window.google?.maps?.importLibrary) {
        return true;
    }

    const key = import.meta.env.VITE_GOOGLE_MAPS_API_KEY;
    if (!key) {
        mapError.value = 'Defina VITE_GOOGLE_MAPS_API_KEY para habilitar o mapa.';
        return false;
    }

    if (!googleMapsLoaderPromise) {
        setOptions({
            key,
            v: 'weekly',
        });

        googleMapsLoaderPromise = Promise.all([
            importGoogleLibrary('maps'),
            importGoogleLibrary('marker'),
        ])
            .then(() => true)
            .catch((error) => {
                googleMapsLoaderPromise = null;
                throw error;
            });
    }

    await googleMapsLoaderPromise;
    return !!window.google?.maps?.importLibrary;
};

const initMap = async () => {
    mapReady.value = false;
    mapError.value = '';
    mapNotice.value = '';

    try {
        const loaded = await loadGoogleMaps();
        if (!loaded) {
            return;
        }

        if (!window.google?.maps || !mapEl.value) {
            return;
        }

        await window.google.maps.importLibrary('maps');

        let AdvancedMarkerElement = null;
        let PinElement = null;
        try {
            ({ AdvancedMarkerElement, PinElement } = await window.google.maps.importLibrary('marker'));
        } catch {
            mapNotice.value = 'Marcadores avançados indisponíveis neste ambiente. Exibindo marcadores padrão.';
        }

        const configuredMapId = String(import.meta.env.VITE_GOOGLE_MAPS_MAP_ID || '').trim();
        renderedMarkerHandles.clear();
        hoveredVenueId.value = null;

        mapInstance = new window.google.maps.Map(mapEl.value, {
            center: { lat: -14.235, lng: -51.9253 },
            zoom: 4,
            ...(configuredMapId ? { mapId: configuredMapId } : {}),
            ...getMapThemeOptions(),
        });
        activeInfoWindow = new window.google.maps.InfoWindow();
        mapReady.value = true;
        mapError.value = '';

        const useAdvancedMarkers = Boolean(AdvancedMarkerElement && PinElement && configuredMapId);
        if (!useAdvancedMarkers && AdvancedMarkerElement) {
            mapNotice.value = 'Marcadores em modo de compatibilidade (customizados).';
        }

        if (!(props.mapPoints || []).length) {
            mapNotice.value = 'Nenhum local com coordenadas para exibir no mapa.';
            return;
        }

        const points = normalizedMapPoints.value;

        const renderLegacyMarkers = async () => {
            const bounds = new window.google.maps.LatLngBounds();
            let rendered = 0;

            for (const point of points) {
                const markerIcon = await buildLegacyMarkerIcon(point, 'default');
                const hoverMarkerIcon = await buildLegacyMarkerIcon(point, 'hover');
                const marker = new window.google.maps.Marker({
                    position: { lat: point.lat, lng: point.lng },
                    map: mapInstance,
                    title: point.name,
                    icon: markerIcon,
                });

                renderedMarkerHandles.set(point.id, {
                    kind: 'legacy',
                    marker,
                    baseIcon: markerIcon,
                    hoverIcon: hoverMarkerIcon,
                });

                marker.addListener('click', () => {
                    activeInfoWindow.setContent(buildInfoWindowContent(point));
                    activeInfoWindow.open({ anchor: marker, map: mapInstance });
                });

                bounds.extend({ lat: point.lat, lng: point.lng });
                rendered += 1;
            }

            return { bounds, rendered };
        };

        const renderAdvancedMarkers = () => {
            const bounds = new window.google.maps.LatLngBounds();
            let rendered = 0;

            points.forEach((point) => {
                const pin = buildPinElement(point, PinElement);
                const marker = new AdvancedMarkerElement({
                    position: { lat: point.lat, lng: point.lng },
                    map: mapInstance,
                    title: point.name,
                    content: pin.element,
                });

                renderedMarkerHandles.set(point.id, {
                    kind: 'advanced',
                    marker,
                    element: pin.element,
                });

                marker.addListener('gmp-click', () => {
                    activeInfoWindow.setContent(buildInfoWindowContent(point));
                    activeInfoWindow.open({ anchor: marker, map: mapInstance });
                });

                bounds.extend({ lat: point.lat, lng: point.lng });
                rendered += 1;
            });

            return { bounds, rendered };
        };

        let bounds = new window.google.maps.LatLngBounds();
        let markersRendered = 0;

        try {
            if (useAdvancedMarkers) {
                ({ bounds, rendered: markersRendered } = renderAdvancedMarkers());
            } else {
                ({ bounds, rendered: markersRendered } = await renderLegacyMarkers());
            }
        } catch (markerError) {
            console.error('Erro ao renderizar markers avançados. Aplicando fallback para markers padrão.', markerError);
            renderedMarkerHandles.clear();
            ({ bounds, rendered: markersRendered } = await renderLegacyMarkers());
            mapNotice.value = 'Alguns marcadores foram renderizados em modo de compatibilidade.';
        }

        if (!markersRendered) {
            mapNotice.value = 'Não foi possível renderizar os marcadores deste filtro.';
            return;
        }

        if (points.length > 1) {
            mapInstance.fitBounds(bounds);
        } else if (points.length === 1) {
            mapInstance.setCenter({ lat: points[0].lat, lng: points[0].lng });
            mapInstance.setZoom(14);
        }

        mapError.value = '';
    } catch (error) {
        console.error('Falha ao inicializar mapa de locais:', error);
        mapReady.value = false;
        mapError.value = 'Falha ao carregar Google Maps.';
    }
};

watch(viewMode, async (value) => {
    if (value === 'map') {
        await nextTick();
        await new Promise((resolve) => window.requestAnimationFrame(resolve));
        initMap();
    }
});

watch(
    () => props.mapPoints,
    async () => {
        if (viewMode.value !== 'map') {
            return;
        }

        await nextTick();
        await new Promise((resolve) => window.requestAnimationFrame(resolve));
        initMap();
    },
    { deep: true }
);

onMounted(() => {
    if (typeof MutationObserver !== 'undefined' && typeof document !== 'undefined') {
        themeObserver = new MutationObserver(() => {
            if (viewMode.value === 'map') {
                applyMapTheme();
            }
        });

        themeObserver.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class'],
        });
    }

    if (viewMode.value === 'map') {
        initMap();
    }
});

onUnmounted(() => {
    if (themeObserver) {
        themeObserver.disconnect();
        themeObserver = null;
    }
});
</script>

<template>
    <div class="space-y-4">
        <BoPageHeader title="Locais" subtitle="Gestão de locais, contatos e geolocalização" icon="mdi:map-marker-multiple-outline">
            <template #actions>
                <div class="hidden md:block">
                    <SelectButton
                        v-model="viewMode"
                        size="small"
                        :options="[
                            { label: 'Lista', value: 'list' },
                            { label: 'Mapa', value: 'map' },
                        ]"
                        option-label="label"
                        option-value="value"
                    />
                </div>
                <Link :href="route('venues.create')">
                    <Button class="!hidden md:!inline-flex" icon="pi pi-plus" label="Novo local" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-plus" rounded aria-label="Novo local" />
                </Link>
            </template>
        </BoPageHeader>

        <BoFilterBar :chips="filterChips" @submit="submitFilters" @reset="resetFilters" @remove-chip="removeChip" @cancel="cancelFilters">
            <div class="space-y-2">
                <label class="text-sm font-medium">Busca</label>
                <IconField>
                    <InputIcon class="pi pi-search" />
                    <InputText v-model="localFilters.search" placeholder="Buscar por nome" />
                </IconField>
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Tipo</label>
                <Select v-model="localFilters.venue_type_id" :options="types" option-label="name" option-value="id" placeholder="Todos os tipos" show-clear />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Status</label>
                <Select v-model="localFilters.status" :options="statusOptions" option-label="label" option-value="value" placeholder="Todos" show-clear />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Cidade</label>
                <InputText v-model="localFilters.city" placeholder="Ex: São Paulo" />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Já tocou</label>
                <Select
                    v-model="localFilters.has_performed"
                    :options="[
                        { label: 'Sim', value: true },
                        { label: 'Não', value: false },
                    ]"
                    option-label="label"
                    option-value="value"
                    placeholder="Todos"
                    show-clear
                />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Avaliação</label>
                <Select v-model="localFilters.rating" :options="[1, 2, 3, 4, 5]" placeholder="Todas" show-clear />
            </div>
        </BoFilterBar>

        <Card v-if="viewMode === 'list'">
                <template #content>
                    <div class="hidden md:block">
                    <DataTable :value="venues.data" data-key="id" striped-rows :sort-mode="'single'" removable-sort>
                    <Column field="name" header="Nome" sortable>
                        <template #body="{ data }">
                            <Link :href="route('venues.show', data.id)" class="font-medium hover:underline">{{ data.name }}</Link>
                        </template>
                    </Column>
                    <Column field="type.name" header="Tipo" sortable />
                    <Column field="city" header="Cidade" sortable />
                    <Column field="status" header="Status" sortable>
                        <template #body="{ data }">
                            <Tag :value="statusLabels[data.status] || data.status || '-'" :style="{ backgroundColor: statusColors[data.status] || '#64748b', color: 'white' }" rounded />
                        </template>
                    </Column>
                    <Column field="rating" header="Avaliação" sortable />
                    <Column field="contact_name" header="Contato" sortable />
                    <Column field="phone" header="Telefone" />
                    <Column field="email" header="E-mail" />
                    <Column header="Ações" class="bo-action-col w-24">
                        <template #body="{ data }">
                            <div class="flex gap-1">
                                <Link :href="route('venues.show', data.id)">
                                    <Button icon="pi pi-eye" size="small" outlined rounded severity="secondary" v-tooltip.top="'Abrir'" />
                                </Link>
                                <Link :href="route('venues.edit', data.id)">
                                    <Button icon="pi pi-pencil" size="small" outlined rounded severity="secondary" v-tooltip.top="'Editar'" />
                                </Link>
                                <BoConfirmButton icon="pi pi-trash" severity="danger" message="Deseja remover este local?" :rounded="true" @confirm="removeVenue(data.id)" />
                            </div>
                        </template>
                    </Column>
                    <template #empty>
                        <BoDataTableEmpty />
                    </template>
                    </DataTable>
                    </div>

                    <div class="space-y-3 md:hidden">
                        <div v-for="venue in venues.data" :key="venue.id" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-start justify-between gap-2">
                                <Link :href="route('venues.show', venue.id)" class="font-semibold hover:underline">{{ venue.name }}</Link>
                                <Tag :value="statusLabels[venue.status] || venue.status || '-'" :style="{ backgroundColor: statusColors[venue.status] || '#64748b', color: 'white' }" rounded />
                            </div>
                            <p class="mt-1 text-xs text-slate-500">Tipo: {{ venue.type?.name || '-' }}</p>
                            <p class="text-xs text-slate-500">Cidade: {{ venue.city || '-' }}</p>
                            <p class="text-xs text-slate-500">Contato: {{ venue.contact_name || '-' }}</p>
                            <p class="text-xs text-slate-500">Telefone: {{ venue.phone || '-' }}</p>
                            <div class="mt-3 flex justify-end gap-1">
                                <Link :href="route('venues.show', venue.id)">
                                    <Button icon="pi pi-eye" size="small" outlined rounded severity="secondary" />
                                </Link>
                                <Link :href="route('venues.edit', venue.id)">
                                    <Button icon="pi pi-pencil" size="small" outlined rounded severity="secondary" />
                                </Link>
                                <BoConfirmButton icon="pi pi-trash" severity="danger" message="Deseja remover este local?" :rounded="true" @confirm="removeVenue(venue.id)" />
                            </div>
                        </div>
                    </div>

                    <Paginator
                        class="mt-4"
                        :rows="venues.per_page"
                        :total-records="venues.total"
                        :first="(venues.current_page - 1) * venues.per_page"
                        @page="paginate"
                    />
                </template>
            </Card>

            <Card v-else>
                <template #content>
                    <div class="lg:grid lg:grid-cols-3 lg:gap-4 xl:grid-cols-4">
                        <div class="lg:col-span-2 xl:col-span-3">
                            <div ref="mapEl" class="h-[34rem] w-full rounded-xl border border-slate-200 dark:border-slate-800" />
                            <p v-if="mapError" class="mt-3 text-sm text-amber-600">{{ mapError }}</p>
                            <p v-else-if="!mapReady" class="mt-3 text-sm text-slate-500">Carregando mapa...</p>
                            <p v-else-if="mapNotice" class="mt-3 text-sm text-slate-500">{{ mapNotice }}</p>
                        </div>

                        <aside class="mt-4 hidden max-h-[34rem] min-h-[34rem] flex-col lg:mt-0 lg:flex">
                            <div class="mb-2 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200">Locais no mapa</h3>
                                <span class="text-xs text-slate-500">{{ normalizedMapPoints.length }}</span>
                            </div>

                            <div class="flex-1 space-y-2 overflow-y-auto pr-1">
                                <button
                                    v-for="point in normalizedMapPoints"
                                    :key="point.id"
                                    type="button"
                                    class="group flex w-full items-stretch overflow-hidden rounded-lg border border-slate-200 bg-white text-left shadow-sm transition hover:border-slate-300 hover:shadow dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700"
                                    @mouseenter="onVenueCardHover(point.id)"
                                    @mouseleave="onVenueCardLeave(point.id)"
                                >
                                    <div class="flex w-14 items-center justify-center" :style="{ background: sanitizeColor(point?.type?.color) }">
                                        <iconify-icon
                                            :icon="sanitizeIcon(point?.type?.icon)"
                                            class="text-[20px] text-white drop-shadow"
                                        />
                                    </div>

                                    <div class="min-w-0 flex-1 p-2.5">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ point.name }}</p>
                                            <p class="shrink-0 text-xs text-amber-500">{{ formatRatingStars(point.rating) }}</p>
                                        </div>
                                        <p class="mt-1 line-clamp-2 text-xs text-slate-500 dark:text-slate-400">{{ point.address || 'Endereço não informado' }}</p>
                                    </div>
                                </button>

                                <p v-if="!normalizedMapPoints.length" class="rounded-lg border border-dashed border-slate-300 px-3 py-4 text-xs text-slate-500 dark:border-slate-700 dark:text-slate-400">
                                    Nenhum local para os filtros atuais.
                                </p>
                            </div>
                        </aside>
                    </div>
                </template>
            </Card>
    </div>
</template>

<style>
@keyframes bo-marker-bounce {
    0%,
    100% {
        transform: translateY(0) scale(1.12);
    }
    40% {
        transform: translateY(-6px) scale(1.12);
    }
    65% {
        transform: translateY(-2px) scale(1.12);
    }
}
</style>

