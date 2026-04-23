<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import ptBrLocale from '@fullcalendar/core/locales/pt-br';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoConfirmButton from '@/Components/ui/BoConfirmButton.vue';
import BoDateText from '@/Components/ui/BoDateText.vue';
import BoFilterBar from '@/Components/ui/BoFilterBar.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({ events: Object, calendarEvents: Array, types: Array, filters: Object });
const viewMode = ref('list');

const viewModeOptions = [
    { label: 'Lista', value: 'list', icon: 'mdi:list-box' },
    { label: 'Calendário completo', value: 'full_calendar', icon: 'mdi:calendar-month' },
];

const attendanceQuickOptions = [
    { label: 'Todos', value: 'all' },
    { label: 'Participante', value: 'participant' },
    { label: 'Como público', value: 'audience' },
];

const attendanceLabels = {
    participant: 'Participante',
    audience: 'Público',
};

const toBoolean = (value) => value === true || value === 1 || value === '1' || value === 'true';

const localFilters = reactive({
    event_type_id: props.filters?.event_type_id ?? null,
    attendance_mode: props.filters?.attendance_mode ?? 'all',
    event_mode: props.filters?.event_mode ?? null,
    search: props.filters?.search ?? '',
    show_past: toBoolean(props.filters?.show_past),
});

const syncLocalFiltersFromProps = () => {
    localFilters.event_type_id = props.filters?.event_type_id ?? null;
    localFilters.attendance_mode = props.filters?.attendance_mode ?? 'all';
    localFilters.event_mode = props.filters?.event_mode ?? null;
    localFilters.search = props.filters?.search ?? '';
    localFilters.show_past = toBoolean(props.filters?.show_past);
};

watch(() => props.filters, syncLocalFiltersFromProps, { deep: true });

const filterChips = computed(() => {
    const chips = [];
    if (localFilters.search) chips.push({ key: 'search', label: localFilters.search });
    if (localFilters.event_type_id) {
        const type = props.types.find((item) => item.id === localFilters.event_type_id);
        if (type) chips.push({ key: 'event_type_id', label: type.name });
    }
    if (localFilters.event_mode) {
        chips.push({
            key: 'event_mode',
            label: localFilters.event_mode === 'online' ? 'Online' : 'Presencial',
        });
    }
    if (localFilters.show_past) chips.push({ key: 'show_past', label: 'Exibindo eventos passados' });
    return chips;
});

const buildQueryPayload = (page = null) => ({
    event_type_id: localFilters.event_type_id,
    attendance_mode: localFilters.attendance_mode === 'all' ? null : localFilters.attendance_mode,
    event_mode: localFilters.event_mode,
    search: localFilters.search || null,
    show_past: localFilters.show_past ? 1 : null,
    page,
});

const submitFilters = () => {
    router.get(route('events.index'), buildQueryPayload(), { preserveState: true, preserveScroll: true, replace: true });
};

const resetFilters = () => {
    localFilters.event_type_id = null;
    localFilters.attendance_mode = 'all';
    localFilters.event_mode = null;
    localFilters.search = '';
    localFilters.show_past = false;
    submitFilters();
};

const removeChip = (key) => {
    if (key === 'search') {
        localFilters.search = '';
    } else if (key === 'show_past') {
        localFilters.show_past = false;
    } else {
        localFilters[key] = null;
    }
    submitFilters();
};

const cancelFilters = () => syncLocalFiltersFromProps();

const paginate = (event) => router.get(route('events.index'), buildQueryPayload(event.page + 1), { preserveState: true, preserveScroll: true, replace: true });

const removeEvent = (id) => router.delete(route('events.destroy', id), { preserveScroll: true });

const setAttendanceFilter = (value) => {
    localFilters.attendance_mode = value;
    submitFilters();
};

const fullCalendarOptions = computed(() => ({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    firstDay: 0,
    locale: 'pt-br',
    locales: [ptBrLocale],
    height: 'auto',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek',
    },
    buttonText: {
        today: 'Hoje',
        month: 'Mês',
        week: 'Semana',
    },
    events: (props.calendarEvents || []).map((item) => ({
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
        <BoPageHeader title="Eventos" subtitle="Agenda da banda com locais, ingressos e participação" icon="ph:ticket-bold">
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
                                <span class="hidden md:inline">{{ slotProps.option.label }}</span>
                            </div>
                        </template>
                    </SelectButton>
                </div>
                <Link :href="route('events.create')">
                    <Button class="!hidden md:!inline-flex" icon="pi pi-plus" label="Novo evento" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-plus" rounded aria-label="Novo evento" />
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
            <template #after-filter-button>
                <div class="ml-auto">
                    <SelectButton
                        :model-value="localFilters.attendance_mode"
                        :options="attendanceQuickOptions"
                        option-label="label"
                        option-value="value"
                        size="small"
                        @update:model-value="setAttendanceFilter"
                    />
                </div>
            </template>

            <div class="space-y-2">
                <label class="text-sm font-medium">Busca</label>
                <InputText v-model="localFilters.search" placeholder="Buscar por título" />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Tipo</label>
                <Select v-model="localFilters.event_type_id" :options="types" option-label="name" option-value="id" show-clear placeholder="Todos" />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Modalidade</label>
                <Select
                    v-model="localFilters.event_mode"
                    :options="[
                        { label: 'Todos', value: null },
                        { label: 'Online', value: 'online' },
                        { label: 'Presencial', value: 'offline' },
                    ]"
                    option-label="label"
                    option-value="value"
                    show-clear
                    placeholder="Todos"
                />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium" for="events-show-past">Exibir eventos passados</label>
                <div class="flex items-center gap-2">
                    <Checkbox id="events-show-past" v-model="localFilters.show_past" binary />
                    <span class="text-sm text-slate-600 dark:text-slate-300">Mostrar eventos passados na lista</span>
                </div>
            </div>
        </BoFilterBar>

        <Card v-if="viewMode === 'list'">
            <template #content>
                <div class="hidden md:block">
                <DataTable :value="events.data" data-key="id" striped-rows>
                    <Column field="title" header="Título">
                        <template #body="{ data }">
                            <Link :href="route('events.show', data.id)" class="font-medium hover:underline">{{ data.title }}</Link>
                        </template>
                    </Column>
                    <Column field="type.name" header="Tipo" />
                    <Column header="Presença">
                        <template #body="{ data }">{{ attendanceLabels[data.attendance_mode] || data.attendance_mode }}</template>
                    </Column>
                    <Column header="Data e hora">
                        <template #body="{ data }"><BoDateText :value="data.starts_at" mode="datetime" /></template>
                    </Column>
                    <Column header="Online" class="w-20">
                        <template #body="{ data }">
                            <Checkbox :model-value="!!data.is_online" binary disabled />
                        </template>
                    </Column>
                    <Column header="Local">
                        <template #body="{ data }">{{ data.is_online ? 'Evento online' : (data.venue?.name || '-') }}</template>
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
                </div>

                <div v-if="events.data.length" class="space-y-3 md:hidden">
                    <div v-for="event in events.data" :key="event.id" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="grid grid-cols-5 gap-3">
                            <div class="col-span-3 space-y-2">
                                <Link :href="route('events.show', event.id)" class="block text-base font-semibold leading-5 hover:underline">{{ event.title }}</Link>

                                <div>
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Presença</p>
                                    <p class="text-sm text-slate-700 dark:text-slate-200">{{ attendanceLabels[event.attendance_mode] || event.attendance_mode }}</p>
                                </div>

                                <div>
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Data e hora</p>
                                    <p class="text-xs text-slate-600 dark:text-slate-300"><BoDateText :value="event.starts_at" mode="datetime" /></p>
                                </div>

                                <div>
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Online</p>
                                    <p class="text-sm text-slate-700 dark:text-slate-200">{{ event.is_online ? 'Sim' : 'Não' }}</p>
                                </div>

                                <div>
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Local</p>
                                    <p class="text-sm text-slate-700 dark:text-slate-200">{{ event.is_online ? 'Evento online' : (event.venue?.name || '-') }}</p>
                                </div>
                            </div>

                            <div class="col-span-2 flex flex-col items-end justify-between gap-3">
                                <Tag :value="event.type?.name || '-'" />

                                <div class="flex flex-wrap justify-end gap-1">
                                    <Link :href="route('events.edit', event.id)"><Button icon="pi pi-pencil" size="small" outlined rounded severity="secondary" /></Link>
                                    <BoConfirmButton icon="pi pi-trash" severity="danger" :rounded="true" message="Deseja remover este evento?" @confirm="removeEvent(event.id)" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="md:hidden">
                    <div class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center dark:border-slate-700 dark:bg-slate-900">
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Nenhum evento ainda.</p>
                        <Link :href="route('events.create')" class="mt-3 inline-flex">
                            <Button label="Crie seu primeiro evento" icon="pi pi-plus" size="small" />
                        </Link>
                    </div>
                </div>

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