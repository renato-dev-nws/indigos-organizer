<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { importLibrary as importGoogleLibrary, setOptions } from '@googlemaps/js-api-loader';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoDateText from '@/Components/ui/BoDateText.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import TaskViewModal from '@/Components/tasks/TaskViewModal.vue';

defineOptions({ layout: AppLayout });

const goBack = () => {
    if (typeof window !== 'undefined' && window.history.length > 1) {
        window.history.back();
    }
};

const props = defineProps({ event: Object });
const activeTask = ref(null);
const taskModalVisible = ref(false);
const venueMapEl = ref(null);

let googleMapsPromise = null;
let venueMap = null;
let venueMarker = null;
let MapCtor = null;
let MarkerCtor = null;
let AdvancedMarkerCtor = null;

const attendanceLabels = {
    participant: 'Como participante',
    audience: 'Como público',
};

const ticketTags = computed(() => ([
    { label: '1º lote', value: props.event.ticket_price_first_batch },
    { label: '2º lote', value: props.event.ticket_price_second_batch },
    { label: '3º lote', value: props.event.ticket_price_third_batch },
    { label: 'Na porta', value: props.event.ticket_price_door },
]).filter((item) => item.value !== null && item.value !== '' && item.value !== undefined));

const openTask = (task) => {
    activeTask.value = task;
    taskModalVisible.value = true;
};

const hasVenueCoordinates = computed(() => {
    const lat = Number(props.event?.venue?.latitude);
    const lng = Number(props.event?.venue?.longitude);

    return Number.isFinite(lat) && Number.isFinite(lng);
});

const ensureGoogleMaps = async () => {
    if (MapCtor) {
        return true;
    }

    if (googleMapsPromise) {
        return googleMapsPromise;
    }

    const key = import.meta.env.VITE_GOOGLE_MAPS_API_KEY;
    if (!key) {
        return false;
    }

    googleMapsPromise = (async () => {
        setOptions({
            key,
            v: 'weekly',
        });

        const mapsLib = await importGoogleLibrary('maps');
        const markerLib = await importGoogleLibrary('marker');
        MapCtor = mapsLib.Map;
        MarkerCtor = window.google?.maps?.Marker ?? null;
        AdvancedMarkerCtor = markerLib.AdvancedMarkerElement ?? window.google?.maps?.marker?.AdvancedMarkerElement ?? null;

        return !!MapCtor;
    })().catch(() => false);

    return googleMapsPromise;
};

const renderVenueMap = async () => {
    if (!venueMapEl.value || !hasVenueCoordinates.value) {
        return;
    }

    const ready = await ensureGoogleMaps();
    if (!ready || !MapCtor) {
        return;
    }

    const position = {
        lat: Number(props.event.venue.latitude),
        lng: Number(props.event.venue.longitude),
    };

    if (!venueMap) {
        venueMap = new MapCtor(venueMapEl.value, {
            center: position,
            zoom: 15,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false,
        });

        if (MarkerCtor) {
            venueMarker = new MarkerCtor({
                map: venueMap,
                position,
            });
        } else if (AdvancedMarkerCtor) {
            venueMarker = new AdvancedMarkerCtor({
                map: venueMap,
                position,
            });
        }

        return;
    }

    venueMap.setCenter(position);
    venueMarker?.setPosition(position);
};

watch(
    () => [props.event?.venue?.id, props.event?.venue?.latitude, props.event?.venue?.longitude],
    async () => {
        await nextTick();
        renderVenueMap();
    },
    { immediate: true },
);
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader :title="event.title" subtitle="Visão completa do evento">
            <template #actions>
                <div>
                    <Button class="!hidden md:!inline-flex" icon="pi pi-arrow-left" label="Voltar" outlined severity="secondary" @click="goBack" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-arrow-left" rounded outlined severity="secondary" aria-label="Voltar" @click="goBack" />
                </div>
                <Link :href="route('events.edit', event.id)">
                    <Button class="!hidden md:!inline-flex" icon="pi pi-pencil" label="Editar" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-pencil" rounded aria-label="Editar" />
                </Link>
            </template>
        </BoPageHeader>

        <Card>
            <template #content>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Tipo</p>
                        <p class="font-semibold">{{ event.type?.name || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Presença</p>
                        <p class="font-semibold">{{ attendanceLabels[event.attendance_mode] || event.attendance_mode }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Data e hora</p>
                        <p class="font-semibold"><BoDateText :value="event.starts_at" mode="datetime" /></p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Local</p>
                        <p class="font-semibold">{{ event.venue?.name || '-' }}</p>
                    </div>
                </div>
            </template>
        </Card>

        <Card>
            <template #title>Descrição</template>
            <template #content>
                <p class="whitespace-pre-line text-sm text-slate-700 dark:text-slate-200">{{ event.description || 'Sem descrição cadastrada.' }}</p>
            </template>
        </Card>

        <div class="grid gap-4 xl:grid-cols-2">
            <Card>
                <template #title>Ingressos</template>
                <template #content>
                    <div class="space-y-3 text-sm">
                        <a v-if="event.ticket_link" :href="event.ticket_link" target="_blank" rel="noopener" class="text-indigo-600 underline dark:text-indigo-400">{{ event.ticket_link }}</a>
                        <p v-else class="text-slate-500">Sem link de ingresso.</p>
                        <div class="flex flex-wrap gap-2">
                            <Tag v-for="ticket in ticketTags" :key="ticket.label" :value="`${ticket.label}: R$ ${Number(ticket.value).toFixed(2)}`" severity="secondary" />
                            <span v-if="!ticketTags.length" class="text-slate-500">Nenhum valor informado.</span>
                        </div>
                    </div>
                </template>
            </Card>

            <Card>
                <template #title>Local</template>
                <template #content>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2 text-sm">
                            <p><strong>Nome:</strong> {{ event.venue?.name || '-' }}</p>
                            <p><strong>Endereço:</strong> {{ event.venue?.address_line || '-' }}, {{ event.venue?.address_number || '-' }}</p>
                            <p><strong>Bairro:</strong> {{ event.venue?.neighborhood || '-' }}</p>
                            <p><strong>Cidade/UF:</strong> {{ event.venue?.city || '-' }} / {{ event.venue?.state || '-' }}</p>
                        </div>

                        <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
                            <div v-if="hasVenueCoordinates" ref="venueMapEl" class="h-44 w-full" />
                            <div v-else class="flex h-44 items-center justify-center text-slate-400 dark:text-slate-500">
                                <div class="text-center">
                                    <iconify-icon icon="ph:map-pin-area-bold" width="32" height="32" />
                                    <p class="mt-2 text-xs">Sem coordenadas para exibir o mapa.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            <Card>
                <template #title>Informações extras</template>
                <template #content>
                    <div class="space-y-3">
                        <div v-for="item in event.extra_infos || []" :key="item.id" class="rounded-xl border border-slate-200/80 p-3 dark:border-slate-800">
                            <p class="font-semibold">{{ item.title }}</p>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ item.information }}</p>
                        </div>
                        <p v-if="!(event.extra_infos || []).length" class="text-sm text-slate-500">Nenhuma informação extra cadastrada.</p>
                    </div>
                </template>
            </Card>

            <Card>
                <template #title>Outros links</template>
                <template #content>
                    <div class="space-y-3">
                        <div v-for="link in event.links || []" :key="link.id" class="rounded-xl border border-slate-200/80 p-3 dark:border-slate-800">
                            <p class="font-semibold">{{ link.title }}</p>
                            <a :href="link.url" target="_blank" rel="noopener" class="mt-1 block text-sm text-indigo-600 underline dark:text-indigo-400">{{ link.url }}</a>
                        </div>
                        <p v-if="!(event.links || []).length" class="text-sm text-slate-500">Nenhum link cadastrado.</p>
                    </div>
                </template>
            </Card>
        </div>

        <Card>
            <template #title>Tarefas relacionadas</template>
            <template #content>
                <div class="grid gap-2 md:grid-cols-2">
                    <button
                        v-for="task in event.tasks || []"
                        :key="task.id"
                        type="button"
                        class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-left transition hover:border-indigo-300 hover:bg-indigo-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-indigo-700 dark:hover:bg-slate-800"
                        @click="openTask(task)"
                    >
                        <p class="text-sm font-semibold">{{ task.title }}</p>
                        <p class="text-xs text-slate-500">{{ task.status?.name || 'Sem status' }}</p>
                    </button>
                    <p v-if="!(event.tasks || []).length" class="text-sm text-slate-500">Nenhuma tarefa vinculada diretamente.</p>
                </div>
            </template>
        </Card>

        <TaskViewModal v-model:visible="taskModalVisible" :task="activeTask" />
    </div>
</template>