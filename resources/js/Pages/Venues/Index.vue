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
import { buildWhatsAppUrl, formatBrazilPhone } from '@/Utils/phone';

defineOptions({ layout: AppLayout });
const props = defineProps({ venues: Object, sizes: Array, types: Array, categories: Array, styles: Array, filters: Object, mapPoints: Array, venueCharts: Object });

const viewMode = ref('list');
const viewModeOptions = [
    { label: 'Lista', value: 'list', icon: 'mdi:list-box' },
    { label: 'Mapa', value: 'map', icon: 'mdi:map-search-outline' },
    { label: 'Gráficos', value: 'charts', icon: 'mdi:chart-box' },
];
const quickSearch = ref(props.filters?.search ?? '');
const quickSearchTimer = ref(null);
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
const selectedMobileMapVenue = ref(null);
const mobileMapSuggestions = ref([]);

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
    venue_category_id: props.filters?.venue_category_id ?? null,
    venue_style_id: props.filters?.venue_style_id ?? null,
    status: props.filters?.status ?? null,
    city: props.filters?.city ?? '',
    has_performed: props.filters?.has_performed ?? null,
    rating: props.filters?.rating ?? null,
    search: props.filters?.search ?? '',
});

const syncLocalFiltersFromProps = () => {
    localFilters.venue_type_id = props.filters?.venue_type_id ?? null;
    localFilters.venue_category_id = props.filters?.venue_category_id ?? null;
    localFilters.venue_style_id = props.filters?.venue_style_id ?? null;
    localFilters.status = props.filters?.status ?? null;
    localFilters.city = props.filters?.city ?? '';
    localFilters.has_performed = props.filters?.has_performed ?? null;
    localFilters.rating = props.filters?.rating ?? null;
    localFilters.search = props.filters?.search ?? '';
    quickSearch.value = props.filters?.search ?? '';
};

const filterChips = computed(() => {
    const chips = [];
    if (localFilters.venue_type_id) {
        const type = props.types?.find((s) => s.id === localFilters.venue_type_id);
        if (type) chips.push({ key: 'venue_type_id', label: type.name });
    }
    if (localFilters.venue_category_id) {
        const category = props.categories?.find((item) => item.id === localFilters.venue_category_id);
        if (category) chips.push({ key: 'venue_category_id', label: category.name });
    }
    if (localFilters.venue_style_id) {
        const style = props.styles?.find((item) => item.id === localFilters.venue_style_id);
        if (style) chips.push({ key: 'venue_style_id', label: style.name });
    }
    if (localFilters.status) {
        const status = statusOptions.find((item) => item.value === localFilters.status);
        chips.push({ key: 'status', label: status?.label || localFilters.status });
    }
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
    localFilters.venue_category_id = null;
    localFilters.venue_style_id = null;
    localFilters.status = null;
    localFilters.city = '';
    localFilters.has_performed = null;
    localFilters.rating = null;
    submitFilters();
};

const removeChip = (key) => {
    localFilters[key] = key === 'city' ? '' : null;
    submitFilters();
};

const cancelFilters = () => {
    syncLocalFiltersFromProps();
};

watch(() => props.filters, syncLocalFiltersFromProps, { deep: true });

watch(quickSearch, (value) => {
    if (quickSearchTimer.value) {
        clearTimeout(quickSearchTimer.value);
    }

    quickSearchTimer.value = setTimeout(() => {
        if (localFilters.search === value) {
            return;
        }

        localFilters.search = value;
        submitFilters();
    }, 1000);
});

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

const whatsappUrl = (value) => buildWhatsAppUrl(value);
const phoneLabel = (value) => formatBrazilPhone(value) || value || '-';

const selectedVenueChart = ref('types');
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: true,
            position: 'top',
        },
    },
};

const barChartOptions = {
    ...chartOptions,
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                stepSize: 1,
                precision: 0,
            },
        },
    },
    plugins: {
        legend: {
            display: false,
        },
    },
};

const venueTypesChartData = computed(() => ({
    labels: props.venueCharts?.types?.labels ?? [],
    datasets: [{ data: props.venueCharts?.types?.data ?? [] }],
}));

const venueCategoriesChartData = computed(() => ({
    labels: props.venueCharts?.categories?.labels ?? [],
    datasets: [{ data: props.venueCharts?.categories?.data ?? [] }],
}));

const venueStylesChartData = computed(() => ({
    labels: props.venueCharts?.styles?.labels ?? [],
    datasets: [{ backgroundColor: '#0ea5e9', data: props.venueCharts?.styles?.data ?? [] }],
}));

const venueStatesChartData = computed(() => ({
    labels: props.venueCharts?.states?.labels ?? [],
    datasets: [{ data: props.venueCharts?.states?.data ?? [] }],
}));

const venueCitiesChartData = computed(() => ({
    labels: props.venueCharts?.cities?.labels ?? [],
    datasets: [{ data: props.venueCharts?.cities?.data ?? [] }],
}));

const selectedVenueChartTitle = computed(() => {
    return {
        types: 'Tipos de locais',
        categories: 'Categorias de locais',
        styles: 'Estilos de locais',
        states: 'Distribuicao por estados',
        cities: 'Distribuicao por cidades',
    }[selectedVenueChart.value] ?? 'Graficos de locais';
});

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

const performancesLabel = (count) => {
    if (count === null || count === undefined || count === '') {
        return 'Não informado';
    }

    const value = Number(count);
    if (!Number.isFinite(value)) {
        return 'Não informado';
    }

    if (value <= 0) {
        return 'Nenhuma';
    }

    if (value === 1) {
        return '1 vez';
    }

    return `${value} vezes`;
};

const normalizeForSearch = (value) => String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase();

const shouldShowMobileStatus = (status) => ['contacted', 'negotiating', 'open_doors'].includes(status);

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

const searchMobileMapPoints = (event) => {
    const query = normalizeForSearch(event?.query || '').trim();

    if (!query) {
        mobileMapSuggestions.value = normalizedMapPoints.value.slice(0, 8);
        return;
    }

    mobileMapSuggestions.value = normalizedMapPoints.value
        .filter((point) => {
            const haystack = normalizeForSearch([
                point.name,
                point.address,
                point.type?.name,
            ].filter(Boolean).join(' '));

            return haystack.includes(query);
        })
        .slice(0, 8);
};

const focusMapPoint = (pointOrId) => {
    if (!mapInstance || !activeInfoWindow) {
        return;
    }

    const pointId = typeof pointOrId === 'string' ? pointOrId : pointOrId?.id;
    if (!pointId) {
        return;
    }

    const point = normalizedMapPoints.value.find((item) => item.id === pointId);
    const handle = renderedMarkerHandles.get(pointId);

    if (!point || !handle) {
        return;
    }

    mapInstance.panTo({ lat: point.lat, lng: point.lng });
    mapInstance.setZoom(Math.max(mapInstance.getZoom() || 0, 14));
    activeInfoWindow.setContent(buildInfoWindowContent(point));

    if (handle.kind === 'advanced') {
        activeInfoWindow.open({ anchor: handle.marker, map: mapInstance });
    } else {
        activeInfoWindow.open(mapInstance, handle.marker);
    }

    if (hoveredVenueId.value && hoveredVenueId.value !== pointId) {
        setMarkerHovered(hoveredVenueId.value, false);
    }

    hoveredVenueId.value = pointId;
    setMarkerHovered(pointId, true);
};

const onMobileMapVenueSelect = (event) => {
    focusMapPoint(event?.value);
};

const clearMobileMapVenue = () => {
    if (hoveredVenueId.value) {
        setMarkerHovered(hoveredVenueId.value, false);
        hoveredVenueId.value = null;
    }

    selectedMobileMapVenue.value = null;
    mobileMapSuggestions.value = normalizedMapPoints.value.slice(0, 8);
};

const buildInfoWindowContent = (point) => {
    const icon = sanitizeIcon(point?.type?.icon);
    const color = sanitizeColor(point?.type?.color);
    const typeName = point?.type?.name || 'Tipo não informado';
    const cityState = [point?.city, point?.state].filter(Boolean).join('/') || 'Cidade/UF não informado';
    const address = point?.address || 'Endereço não informado';
    const compactAddress = address.length > 96 ? `${address.slice(0, 96)}...` : address;

    return `
        <div style="min-width:220px;max-width:260px;max-height:190px;overflow:auto;padding:4px 2px;font-family:inherit;">
            <p style="margin:0 0 8px 0;font-size:13px;font-weight:700;color:#0f172a;line-height:1.25;">${escapeHtml(point?.name || '')}</p>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;color:#334155;">
                <span style="display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:9999px;background:${escapeHtml(color)};color:#fff;flex-shrink:0;">
                    <img src="${toIconSvgUrl(icon)}" alt="ícone" width="18" height="18" style="display:block;" />
                </span>
                <span style="font-size:12px;line-height:1.2;">${escapeHtml(typeName)}</span>
            </div>
            <p style="margin:0 0 6px 0;font-size:11px;color:#475569;">${escapeHtml(cityState)}</p>
            <p style="margin:0;font-size:11px;color:#64748b;line-height:1.35;">${escapeHtml(compactAddress)}</p>
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
        mobileMapSuggestions.value = normalizedMapPoints.value.slice(0, 8);

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
    mobileMapSuggestions.value = normalizedMapPoints.value.slice(0, 8);

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
    if (quickSearchTimer.value) {
        clearTimeout(quickSearchTimer.value);
    }

    if (themeObserver) {
        themeObserver.disconnect();
        themeObserver = null;
    }
});
</script>

<template>
    <div class="space-y-4">
        <BoPageHeader title="Locais" subtitle="Gestão de locais, contatos e geolocalização" helper="locais" icon="mdi:map-marker-multiple-outline">
            <template #actions>
                <div class="hidden md:block">
                    <SelectButton
                        v-model="viewMode"
                        size="small"
                        :options="viewModeOptions"
                        option-label="label"
                        option-value="value"
                    >
                        <template #option="slotProps">
                            <div class="flex items-center gap-2">
                                <iconify-icon :icon="slotProps.option.icon" width="16" height="16" />
                                <span>{{ slotProps.option.label }}</span>
                            </div>
                        </template>
                    </SelectButton>
                </div>
                <Link :href="route('venues.create')">
                    <Button class="!hidden md:!inline-flex" icon="pi pi-plus" label="Novo local" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-plus" rounded aria-label="Novo local" />
                </Link>
            </template>
        </BoPageHeader>

        <div class="md:hidden">
            <SelectButton
                v-model="viewMode"
                size="small"
                :options="viewModeOptions"
                option-label="label"
                option-value="value"
            >
                <template #option="slotProps">
                    <div class="flex items-center justify-center">
                        <iconify-icon :icon="slotProps.option.icon" width="16" height="16" />
                    </div>
                </template>
            </SelectButton>
        </div>

        <BoFilterBar :chips="filterChips" @submit="submitFilters" @reset="resetFilters" @remove-chip="removeChip" @cancel="cancelFilters">
            <template #right-actions>
                <div class="relative">
                    <InputText v-model="quickSearch" class="w-72 pr-8" placeholder="Busca rápida de locais" />
                    <button
                        v-if="quickSearch"
                        type="button"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                        aria-label="Limpar busca"
                        @click="quickSearch = ''"
                    >
                        <i class="pi pi-times-circle" />
                    </button>
                </div>
            </template>
            <div class="space-y-2">
                <label class="text-sm font-medium">Tipo</label>
                <Select v-model="localFilters.venue_type_id" :options="types" option-label="name" option-value="id" placeholder="Todos os tipos" show-clear />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Categoria</label>
                <Select v-model="localFilters.venue_category_id" :options="categories" option-label="name" option-value="id" placeholder="Todas" show-clear />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Estilo</label>
                <Select v-model="localFilters.venue_style_id" :options="styles" option-label="name" option-value="id" placeholder="Todos" show-clear />
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
                    <Column field="category.name" header="Categoria" sortable />
                    <Column header="Estilos">
                        <template #body="{ data }">
                            <div class="flex flex-wrap gap-1">
                                <Tag
                                    v-for="style in data.styles || []"
                                    :key="style.id"
                                    severity="secondary"
                                    class="!px-1.5 !py-0.5"
                                >
                                    <template #default>
                                        <iconify-icon :icon="style.icon || 'mdi:palette-outline'" width="14" height="14" />
                                    </template>
                                </Tag>
                                <span v-if="!(data.styles || []).length" class="text-xs text-slate-400">-</span>
                            </div>
                        </template>
                    </Column>
                    <Column header="Avaliação" sortable>
                        <template #body="{ data }">
                            <span class="text-amber-500">{{ formatRatingStars(data.rating) }}</span>
                        </template>
                    </Column>
                    <Column header="WhatsApp">
                        <template #body="{ data }">
                            <a
                                v-if="whatsappUrl(data.whatsapp)"
                                :href="whatsappUrl(data.whatsapp)"
                                target="_blank"
                                rel="noopener"
                                class="text-emerald-600 underline dark:text-emerald-400"
                            >
                                {{ phoneLabel(data.whatsapp) }}
                            </a>
                            <span v-else>-</span>
                        </template>
                    </Column>
                    <Column header="Cidade/UF" sortable>
                        <template #body="{ data }">
                            {{ [data.city, data.state].filter(Boolean).join('/') || '-' }}
                        </template>
                    </Column>
                    <Column header="Apresentou" sortable>
                        <template #body="{ data }">
                            {{ performancesLabel(data.performances_count) }}
                        </template>
                    </Column>
                    <Column field="status" header="Status" sortable>
                        <template #body="{ data }">
                            <Tag :value="statusLabels[data.status] || data.status || '-'" :style="{ backgroundColor: statusColors[data.status] || '#64748b', color: 'white' }" rounded />
                        </template>
                    </Column>
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

                    <div v-if="venues.data.length" class="space-y-3 md:hidden">
                        <div v-for="venue in venues.data" :key="venue.id" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <div class="grid grid-cols-5 gap-3">
                                <div class="col-span-3 space-y-2">
                                    <Link :href="route('venues.show', venue.id)" class="block text-base font-semibold leading-5 hover:underline">{{ venue.name }}</Link>

                                    <div>
                                        <p class="text-[11px] uppercase tracking-wide text-slate-500">Tipo</p>
                                        <p class="text-sm text-slate-700 dark:text-slate-200">{{ venue.type?.name || '-' }}</p>
                                    </div>

                                    <div>
                                        <p class="text-[11px] uppercase tracking-wide text-slate-500">Categoria</p>
                                        <p class="text-sm text-slate-700 dark:text-slate-200">{{ venue.category?.name || '-' }}</p>
                                    </div>

                                    <div>
                                        <p class="text-[11px] uppercase tracking-wide text-slate-500">Cidade/UF</p>
                                        <p class="text-sm text-slate-700 dark:text-slate-200">{{ [venue.city, venue.state].filter(Boolean).join('/') || '-' }}</p>
                                    </div>

                                    <div>
                                        <p class="text-[11px] uppercase tracking-wide text-slate-500">WhatsApp</p>
                                        <p class="text-sm text-slate-700 dark:text-slate-200">
                                            <a v-if="whatsappUrl(venue.whatsapp)" :href="whatsappUrl(venue.whatsapp)" target="_blank" rel="noopener" class="text-emerald-600 underline dark:text-emerald-400">{{ phoneLabel(venue.whatsapp) }}</a>
                                            <span v-else>-</span>
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-[11px] uppercase tracking-wide text-slate-500">Apresentou</p>
                                        <p class="text-sm text-slate-700 dark:text-slate-200">{{ performancesLabel(venue.performances_count) }}</p>
                                    </div>
                                </div>

                                <div class="col-span-2 flex flex-col items-end justify-between gap-3">
                                    <div class="flex flex-col items-end gap-1">
                                        <Tag v-if="shouldShowMobileStatus(venue.status)" :value="statusLabels[venue.status] || venue.status || '-'" :style="{ backgroundColor: statusColors[venue.status] || '#64748b', color: 'white' }" rounded />
                                        <Tag severity="secondary" class="!px-1.5 !py-0.5">{{ formatRatingStars(venue.rating) }}</Tag>
                                    </div>

                                    <div class="flex flex-wrap justify-end gap-1">
                                        <Link :href="route('venues.edit', venue.id)">
                                            <Button icon="pi pi-pencil" size="small" outlined rounded severity="secondary" />
                                        </Link>
                                        <BoConfirmButton icon="pi pi-trash" severity="danger" message="Deseja remover este local?" :rounded="true" @confirm="removeVenue(venue.id)" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="md:hidden">
                        <div class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center dark:border-slate-700 dark:bg-slate-900">
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Nenhum local ainda.</p>
                            <Link :href="route('venues.create')" class="mt-3 inline-flex">
                                <Button label="Crie seu primeiro local" icon="pi pi-plus" size="small" />
                            </Link>
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

            <Card v-else-if="viewMode === 'map'">
                <template #content>
                    <div class="mb-3 lg:hidden">
                        <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Buscar local no mapa</label>
                        <div class="flex items-start gap-2">
                            <AutoComplete
                                v-model="selectedMobileMapVenue"
                                :suggestions="mobileMapSuggestions"
                                option-label="name"
                                fluid
                                dropdown
                                placeholder="Digite para localizar no mapa"
                                @complete="searchMobileMapPoints"
                                @item-select="onMobileMapVenueSelect"
                            >
                                <template #option="slotProps">
                                    <div class="py-1">
                                        <p class="text-sm font-medium">{{ slotProps.option.name }}</p>
                                        <p class="line-clamp-1 text-xs text-slate-500">{{ slotProps.option.address || 'Endereço não informado' }}</p>
                                    </div>
                                </template>
                            </AutoComplete>
                            <Button
                                v-if="selectedMobileMapVenue"
                                type="button"
                                icon="pi pi-times"
                                outlined
                                severity="secondary"
                                aria-label="Limpar busca"
                                @click="clearMobileMapVenue"
                            />
                        </div>
                    </div>

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

            <Card v-else>
                <template #title>
                    <div class="space-y-3">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">Graficos de locais</h3>
                        <Select
                            v-model="selectedVenueChart"
                            size="small"
                            :options="[
                                { label: 'Tipos (donnut)', value: 'types' },
                                { label: 'Categorias (donnut)', value: 'categories' },
                                { label: 'Estilos (barras)', value: 'styles' },
                                { label: 'Estados/UF (pie)', value: 'states' },
                                { label: 'Cidades (pie)', value: 'cities' },
                            ]"
                            option-label="label"
                            option-value="value"
                            class="!w-40"
                        />
                    </div>
                </template>
                <template #content>
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ selectedVenueChartTitle }}</p>
                    <div class="h-[350px] md:h-[400px]">
                        <Chart v-if="selectedVenueChart === 'types'" class="bo-chart-fill" type="doughnut" :data="venueTypesChartData" :options="chartOptions" />
                        <Chart v-else-if="selectedVenueChart === 'categories'" class="bo-chart-fill" type="doughnut" :data="venueCategoriesChartData" :options="chartOptions" />
                        <Chart v-else-if="selectedVenueChart === 'styles'" class="bo-chart-fill" type="bar" :data="venueStylesChartData" :options="barChartOptions" />
                        <Chart v-else-if="selectedVenueChart === 'states'" class="bo-chart-fill" type="pie" :data="venueStatesChartData" :options="chartOptions" />
                        <Chart v-else class="bo-chart-fill" type="pie" :data="venueCitiesChartData" :options="chartOptions" />
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

