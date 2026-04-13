<script setup>
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue';
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
const legacyMarkerIconCache = new Map();

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

const escapeHtml = (value = '') => String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

const sanitizeIcon = (icon) => (/^[a-z0-9-]+:[a-z0-9-]+$/i.test(icon || '') ? icon : DEFAULT_MARKER_ICON);
const sanitizeColor = (color) => (/^#[0-9a-fA-F]{6}$/.test(color || '') ? color : DEFAULT_MARKER_COLOR);
const isDarkMode = () => typeof document !== 'undefined' && document.documentElement.classList.contains('dark');

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

const buildLegacyMarkerIcon = async (point) => {
    const icon = sanitizeIcon(point?.type?.icon);
    const color = sanitizeColor(point?.type?.color);
    const cacheKey = `marker:${icon}:${color}`;

    if (legacyMarkerIconCache.has(cacheKey)) {
        return legacyMarkerIconCache.get(cacheKey);
    }

    const payload = await getIconSvgPayload(icon);
    const svg = buildLegacyMarkerSvg(color, payload);
    const markerIcon = {
        url: `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`,
        scaledSize: new window.google.maps.Size(40, 52),
        anchor: new window.google.maps.Point(20, 38),
    };
    legacyMarkerIconCache.set(cacheKey, markerIcon);
    return markerIcon;
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

        mapInstance = new window.google.maps.Map(mapEl.value, {
            center: { lat: -14.235, lng: -51.9253 },
            zoom: 4,
            ...(configuredMapId ? { mapId: configuredMapId } : {}),
            ...(isDarkMode() ? { styles: DARK_MAP_STYLES, backgroundColor: '#0b1220' } : {}),
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

        const points = (props.mapPoints || []).map((point) => {
            const lat = Number(point?.lat);
            const lng = Number(point?.lng);
            return {
                ...point,
                lat,
                lng,
                isValid: Number.isFinite(lat) && Number.isFinite(lng),
            };
        }).filter((point) => point.isValid);

        const renderLegacyMarkers = async () => {
            const bounds = new window.google.maps.LatLngBounds();
            let rendered = 0;

            for (const point of points) {
                const markerIcon = await buildLegacyMarkerIcon(point);
                const marker = new window.google.maps.Marker({
                    position: { lat: point.lat, lng: point.lng },
                    map: mapInstance,
                    title: point.name,
                    icon: markerIcon,
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
                const marker = new AdvancedMarkerElement({
                    position: { lat: point.lat, lng: point.lng },
                    map: mapInstance,
                    title: point.name,
                    content: buildPinElement(point, PinElement).element,
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
    if (viewMode.value === 'map') {
        initMap();
    }
});
</script>

<template>
    <div class="space-y-4">
        <BoPageHeader title="Locais" subtitle="Gestão de locais, contatos e geolocalização">
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

        <BoFilterBar :chips="filterChips" @submit="submitFilters" @reset="resetFilters" @remove-chip="removeChip">
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
                    <DataTable :value="venues.data" data-key="id" striped-rows :sort-mode="'single'" removable-sort>
                    <Column field="name" header="Nome" sortable />
                    <Column field="type.name" header="Tipo" sortable />
                    <Column field="city" header="Cidade" sortable />
                    <Column field="status" header="Status" sortable />
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
                    <div ref="mapEl" class="h-[34rem] w-full rounded-xl border border-slate-200 dark:border-slate-800" />
                    <p v-if="mapError" class="mt-3 text-sm text-amber-600">{{ mapError }}</p>
                    <p v-else-if="!mapReady" class="mt-3 text-sm text-slate-500">Carregando mapa...</p>
                    <p v-else-if="mapNotice" class="mt-3 text-sm text-slate-500">{{ mapNotice }}</p>
                </template>
            </Card>
    </div>
</template>

