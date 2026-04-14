<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoConfirmButton from '@/Components/ui/BoConfirmButton.vue';
import BoDateText from '@/Components/ui/BoDateText.vue';
import BoFilterBar from '@/Components/ui/BoFilterBar.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({ events: Object, types: Array, filters: Object });
const viewMode = ref('list');

const attendanceLabels = {
    participant: 'Participante',
    audience: 'Público',
};

const localFilters = reactive({
    event_type_id: props.filters?.event_type_id ?? null,
    attendance_mode: props.filters?.attendance_mode ?? null,
    search: props.filters?.search ?? '',
});

const syncLocalFiltersFromProps = () => {
    localFilters.event_type_id = props.filters?.event_type_id ?? null;
    localFilters.attendance_mode = props.filters?.attendance_mode ?? null;
    localFilters.search = props.filters?.search ?? '';
};

watch(() => props.filters, syncLocalFiltersFromProps, { deep: true });

const filterChips = computed(() => {
    const chips = [];
    if (localFilters.search) chips.push({ key: 'search', label: localFilters.search });
    if (localFilters.attendance_mode) chips.push({ key: 'attendance_mode', label: attendanceLabels[localFilters.attendance_mode] || localFilters.attendance_mode });
    if (localFilters.event_type_id) {
        const type = props.types.find((item) => item.id === localFilters.event_type_id);
        if (type) chips.push({ key: 'event_type_id', label: type.name });
    }
    return chips;
});

const submitFilters = () => router.get(route('events.index'), localFilters, { preserveState: true, preserveScroll: true, replace: true });
const resetFilters = () => {
    localFilters.event_type_id = null;
    localFilters.attendance_mode = null;
    localFilters.search = '';
    submitFilters();
};
const removeChip = (key) => {
    localFilters[key] = key === 'search' ? '' : null;
    submitFilters();
};
const cancelFilters = () => syncLocalFiltersFromProps();
const paginate = (event) => router.get(route('events.index'), { ...localFilters, page: event.page + 1 }, { preserveState: true, preserveScroll: true, replace: true });
const removeEvent = (id) => router.delete(route('events.destroy', id), { preserveScroll: true });

const fullCalendarOptions = computed(() => ({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    firstDay: 0,
    locale: 'pt-br',
    height: 'auto',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek',
    },
    events: (props.events.data || []).map((item) => ({
        id: item.id,
        title: item.title,
        start: item.starts_at,
        backgroundColor: item.type?.color || '#059669',
        borderColor: item.type?.color || '#059669',
    })),
    eventClick: (info) => {
        info.jsEvent.preventDefault();
        router.visit(route('events.show', info.event.id));
    },
}));
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Eventos" subtitle="Agenda da banda com locais, ingressos e participação">
            <template #actions>
                <div class="hidden md:block">
                    <SelectButton
                        v-model="viewMode"
                        size="small"
                        :options="[
                            { label: 'Lista', value: 'list' },
                            { label: 'Calendário completo', value: 'full_calendar' },
                        ]"
                        option-label="label"
                        option-value="value"
                    />
                </div>
                <Link :href="route('events.create')">
                    <Button class="!hidden md:!inline-flex" icon="pi pi-plus" label="Novo evento" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-plus" rounded aria-label="Novo evento" />
                </Link>
            </template>
        </BoPageHeader>

        <BoFilterBar :chips="filterChips" @submit="submitFilters" @reset="resetFilters" @remove-chip="removeChip" @cancel="cancelFilters">
            <div class="space-y-2">
                <label class="text-sm font-medium">Busca</label>
                <InputText v-model="localFilters.search" placeholder="Buscar por título" />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Tipo</label>
                <Select v-model="localFilters.event_type_id" :options="types" option-label="name" option-value="id" show-clear placeholder="Todos" />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Presença</label>
                <Select
                    v-model="localFilters.attendance_mode"
                    :options="[
                        { label: 'Participante', value: 'participant' },
                        { label: 'Público', value: 'audience' },
                    ]"
                    option-label="label"
                    option-value="value"
                    show-clear
                    placeholder="Todas"
                />
            </div>
        </BoFilterBar>

        <Card v-if="viewMode === 'list'">
            <template #content>
                <DataTable :value="events.data" data-key="id" striped-rows>
                    <Column field="title" header="Título" />
                    <Column field="type.name" header="Tipo" />
                    <Column header="Presença">
                        <template #body="{ data }">{{ attendanceLabels[data.attendance_mode] || data.attendance_mode }}</template>
                    </Column>
                    <Column header="Data e hora">
                        <template #body="{ data }"><BoDateText :value="data.starts_at" mode="datetime" /></template>
                    </Column>
                    <Column header="Local">
                        <template #body="{ data }">{{ data.venue?.name || '-' }}</template>
                    </Column>
                    <Column header="Ações" class="bo-action-col w-28">
                        <template #body="{ data }">
                            <div class="flex gap-1">
                                <Link :href="route('events.show', data.id)"><Button icon="pi pi-eye" size="small" outlined rounded severity="secondary" /></Link>
                                <Link :href="route('events.edit', data.id)"><Button icon="pi pi-pencil" size="small" outlined rounded severity="secondary" /></Link>
                                <BoConfirmButton icon="pi pi-trash" severity="danger" :rounded="true" message="Deseja remover este evento?" @confirm="removeEvent(data.id)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
                <Paginator class="mt-4" :rows="events.per_page" :total-records="events.total" :first="(events.current_page - 1) * events.per_page" @page="paginate" />
            </template>
        </Card>

        <Card v-else>
            <template #content>
                <FullCalendar :options="fullCalendarOptions" />
            </template>
        </Card>
    </div>
</template>