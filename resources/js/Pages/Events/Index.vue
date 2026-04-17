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
        <BoPageHeader title="Eventos" subtitle="Agenda da banda com locais, ingressos e participação" icon="ph:ticket-bold">
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

                <div class="space-y-3 md:hidden">
                    <div v-for="event in events.data" :key="event.id" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-start justify-between gap-2">
                            <Link :href="route('events.show', event.id)" class="font-semibold hover:underline">{{ event.title }}</Link>
                            <Tag :value="event.type?.name || '-'"></Tag>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Presença: {{ attendanceLabels[event.attendance_mode] || event.attendance_mode }}</p>
                        <p class="text-xs text-slate-500">Data: <BoDateText :value="event.starts_at" mode="datetime" /></p>
                        <p class="text-xs text-slate-500">Online: {{ event.is_online ? 'Sim' : 'Não' }}</p>
                        <p class="text-xs text-slate-500">Local: {{ event.is_online ? 'Evento online' : (event.venue?.name || '-') }}</p>
                        <div class="mt-3 flex justify-end gap-1">
                            <Link :href="route('events.show', event.id)"><Button icon="pi pi-eye" size="small" outlined rounded severity="secondary" /></Link>
                            <Link :href="route('events.edit', event.id)"><Button icon="pi pi-pencil" size="small" outlined rounded severity="secondary" /></Link>
                            <BoConfirmButton icon="pi pi-trash" severity="danger" :rounded="true" message="Deseja remover este evento?" @confirm="removeEvent(event.id)" />
                        </div>
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