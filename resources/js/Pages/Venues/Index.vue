<script setup>
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
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

const loadGoogleMaps = async () => {
    if (window.google?.maps) {
        return;
    }

    const key = import.meta.env.VITE_GOOGLE_MAPS_API_KEY;
    if (!key) {
        mapError.value = 'Defina VITE_GOOGLE_MAPS_API_KEY para habilitar o mapa.';
        return;
    }

    await new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=${key}`;
        script.async = true;
        script.defer = true;
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
    });
};

const initMap = async () => {
    try {
        await loadGoogleMaps();
        if (!window.google?.maps || !mapEl.value) {
            return;
        }

        const map = new window.google.maps.Map(mapEl.value, {
            center: { lat: -14.235, lng: -51.9253 },
            zoom: 4,
        });

        const bounds = new window.google.maps.LatLngBounds();
        (props.mapPoints || []).forEach((point) => {
            const marker = new window.google.maps.Marker({
                position: { lat: point.lat, lng: point.lng },
                map,
                title: point.name,
            });

            marker.addListener('click', () => {
                router.visit(route('venues.show', point.id));
            });

            bounds.extend({ lat: point.lat, lng: point.lng });
        });

        if ((props.mapPoints || []).length) {
            map.fitBounds(bounds);
        }

        mapReady.value = true;
    } catch {
        mapError.value = 'Falha ao carregar Google Maps.';
    }
};

watch(viewMode, async (value) => {
    if (value === 'map') {
        await nextTick();
        initMap();
    }
});

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

        <div class="grid gap-4 lg:grid-cols-[13rem_1fr]">
            <Card>
                <template #content>
                    <div class="flex flex-col gap-2">
                        <Button :outlined="viewMode !== 'list'" icon="pi pi-list" label="Lista" @click="viewMode = 'list'" />
                        <Button :outlined="viewMode !== 'map'" icon="pi pi-map" label="Mapa" @click="viewMode = 'map'" />
                    </div>
                </template>
            </Card>

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
                </template>
            </Card>
        </div>
    </div>
</template>

