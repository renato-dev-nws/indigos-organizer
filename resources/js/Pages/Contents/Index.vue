<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import ptBrLocale from '@fullcalendar/core/locales/pt-br';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFilterBar from '@/Components/ui/BoFilterBar.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoStatusTag from '@/Components/ui/BoStatusTag.vue';
import BoDataTableEmpty from '@/Components/ui/BoDataTableEmpty.vue';
import BoConfirmButton from '@/Components/ui/BoConfirmButton.vue';
import BoDateText from '@/Components/ui/BoDateText.vue';

defineOptions({ layout: AppLayout });
const props = defineProps({ contents: Object, filters: Object, platforms: Array, types: Array, categories: Array, styles: Array, contentCharts: Object, contentChartPeriod: Object });

const viewMode = ref('list');
const weeklyBaseDate = new Date();
weeklyBaseDate.setHours(0, 0, 0, 0);
const todayDate = ref(weeklyBaseDate);

const viewModeOptions = [
    { label: 'Lista', value: 'list', icon: 'mdi:list-box' },
    { label: 'Semana', value: 'calendar', icon: 'mdi:calendar-week' },
    { label: 'Calendário', value: 'full_calendar', icon: 'mdi:calendar-month' },
    { label: 'Gráficos', value: 'charts', icon: 'mdi:chart-box' },
];

const statusLabels = {
    queued: 'Na fila',
    in_production: 'Em produção',
    finalized: 'Finalizado',
    cancelled: 'Cancelado',
    paused: 'Pausado',
    published: 'Publicado',
};

const statusColors = {
    queued: '#94a3b8',
    in_production: '#3b82f6',
    finalized: '#0ea5e9',
    cancelled: '#ef4444',
    paused: '#f59e0b',
    published: '#10b981',
};

const localFilters = reactive({
    status: props.filters?.status ?? null,
    content_platform_id: props.filters?.content_platform_id ?? null,
    idea_type_id: props.filters?.idea_type_id ?? null,
    idea_category_id: props.filters?.idea_category_id ?? null,
    venue_style_id: props.filters?.venue_style_id ?? null,
    planned_week: props.filters?.planned_week ?? '',
    search: props.filters?.search ?? '',
});

const syncLocalFiltersFromProps = () => {
    localFilters.status = props.filters?.status ?? null;
    localFilters.content_platform_id = props.filters?.content_platform_id ?? null;
    localFilters.idea_type_id = props.filters?.idea_type_id ?? null;
    localFilters.idea_category_id = props.filters?.idea_category_id ?? null;
    localFilters.venue_style_id = props.filters?.venue_style_id ?? null;
    localFilters.planned_week = props.filters?.planned_week ?? '';
    localFilters.search = props.filters?.search ?? '';
};

const filterChips = computed(() => {
    const chips = [];
    if (localFilters.search) chips.push({ key: 'search', label: localFilters.search });
    if (localFilters.status) chips.push({ key: 'status', label: statusLabels[localFilters.status] || localFilters.status });
    if (localFilters.content_platform_id) {
        const p = props.platforms?.find((x) => x.id === localFilters.content_platform_id);
        if (p) chips.push({ key: 'content_platform_id', label: p.name });
    }
    if (localFilters.idea_type_id) {
        const t = props.types?.find((x) => x.id === localFilters.idea_type_id);
        if (t) chips.push({ key: 'idea_type_id', label: t.name });
    }
    if (localFilters.idea_category_id) {
        const c = props.categories?.find((x) => x.id === localFilters.idea_category_id);
        if (c) chips.push({ key: 'idea_category_id', label: c.name });
    }
    if (localFilters.venue_style_id) {
        const s = props.styles?.find((x) => x.id === localFilters.venue_style_id);
        if (s) chips.push({ key: 'venue_style_id', label: s.name });
    }
    if (localFilters.planned_week) chips.push({ key: 'planned_week', label: `Semana: ${localFilters.planned_week}` });
    return chips;
});

const submitFilters = () => {
    router.get(route('contents.index'), localFilters, { preserveState: true, preserveScroll: true, replace: true });
};

const resetFilters = () => {
    localFilters.status = null;
    localFilters.content_platform_id = null;
    localFilters.idea_type_id = null;
    localFilters.idea_category_id = null;
    localFilters.venue_style_id = null;
    localFilters.planned_week = '';
    localFilters.search = '';
    submitFilters();
};

const removeChip = (key) => {
    localFilters[key] = ['search', 'planned_week'].includes(key) ? '' : null;
    submitFilters();
};

const cancelFilters = () => {
    syncLocalFiltersFromProps();
};

watch(() => props.filters, syncLocalFiltersFromProps, { deep: true });

const paginate = (event) => {
    router.get(route('contents.index'), { ...localFilters, page: event.page + 1 }, { preserveState: true, preserveScroll: true, replace: true });
};

const removeContent = (id) => router.delete(route('contents.destroy', id), { preserveScroll: true });

const publishMeta = (content) => {
    const isPublished = content?.status === 'published' && !!content?.published_at;

    if (isPublished) {
        return {
            value: content.published_at,
            icon: 'mdi:calendar-check',
            severity: 'success',
            label: 'Publicado',
        };
    }

    return {
        value: content?.planned_publish_at,
        icon: 'mdi:calendar',
        severity: 'warning',
        label: 'Programado',
    };
};

const contentCalendarDate = (content) => {
    if (!content) {
        return null;
    }

    if (content.status === 'published' && content.published_at) {
        return content.published_at;
    }

    return content.planned_publish_at || null;
};

const calendarColumns = computed(() => {
    const labels = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Próximo domingo'];
    const now = new Date();
    const weekStart = new Date(now);
    weekStart.setDate(now.getDate() - now.getDay());
    weekStart.setHours(0, 0, 0, 0);

    const grouped = Array.from({ length: 8 }).map((_, index) => {
        const date = new Date(weekStart);
        date.setDate(weekStart.getDate() + index);

        return {
            index,
            label: labels[index],
            date,
            items: [],
        };
    });

    for (const item of props.contents.data || []) {
        const calendarDate = contentCalendarDate(item);
        if (!calendarDate) {
            continue;
        }

        const date = new Date(calendarDate);
        date.setHours(0, 0, 0, 0);

        const diffDays = Math.round((date.getTime() - weekStart.getTime()) / 86_400_000);
        if (diffDays >= 0 && diffDays <= 7) {
            grouped[diffDays].items.push(item);
        }
    }

    return grouped;
});

const isWeeklyColumnOpenByDefault = (column) => column.date >= todayDate.value;
const contentWeekDate = (content) => contentCalendarDate(content);

const formatWeekDate = (value) => {
    const date = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(date.getTime())) {
        return '';
    }

    return date.toLocaleDateString('pt-BR');
};

const contentCalendarLegend = [
    { key: 'queued', label: statusLabels.queued, color: statusColors.queued },
    { key: 'in_production', label: statusLabels.in_production, color: statusColors.in_production },
    { key: 'finalized', label: statusLabels.finalized, color: statusColors.finalized },
    { key: 'published', label: statusLabels.published, color: statusColors.published },
    { key: 'cancelled', label: statusLabels.cancelled, color: statusColors.cancelled },
    { key: 'paused', label: statusLabels.paused, color: statusColors.paused },
];

const fullCalendarOptions = computed(() => ({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    firstDay: 0,
    locale: 'pt-br',
    locales: [ptBrLocale],
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
    events: (props.contents.data || [])
        .filter((item) => !!contentCalendarDate(item))
        .map((item) => ({
            id: item.id,
            title: item.title,
            start: contentCalendarDate(item),
            backgroundColor: statusColors[item.status] || '#64748b',
            borderColor: statusColors[item.status] || '#64748b',
        })),
    eventClick: (info) => {
        info.jsEvent.preventDefault();
        router.visit(route('contents.show', info.event.id));
    },
}));

const selectedContentChart = ref('line');
const selectedContentChartPeriod = ref(props.contentChartPeriod?.period ?? '7d');
const selectedContentChartStart = ref(props.contentChartPeriod?.start ?? '');
const selectedContentChartEnd = ref(props.contentChartPeriod?.end ?? '');

watch(
    () => props.contentChartPeriod,
    (value) => {
        selectedContentChartPeriod.value = value?.period ?? '7d';
        selectedContentChartStart.value = value?.start ?? '';
        selectedContentChartEnd.value = value?.end ?? '';
    },
    { deep: true },
);

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
};

const chartWithoutLegendOptions = {
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

const doughnutChartOptions = {
    ...chartOptions,
    plugins: {
        legend: {
            display: true,
            position: 'top',
        },
    },
};

const contentLineChartData = computed(() => ({
    labels: props.contentCharts?.lineCreatedPublished?.labels ?? [],
    datasets: [
        {
            type: 'line',
            label: 'Conteúdos criados',
            borderColor: '#2563eb',
            backgroundColor: '#2563eb',
            tension: 0.3,
            data: props.contentCharts?.lineCreatedPublished?.created ?? [],
        },
        {
            type: 'line',
            label: 'Conteúdos publicados',
            borderColor: '#16a34a',
            backgroundColor: '#16a34a',
            tension: 0.3,
            data: props.contentCharts?.lineCreatedPublished?.published ?? [],
        },
    ],
}));

const contentTypesChartData = computed(() => ({
    labels: props.contentCharts?.types?.labels ?? [],
    datasets: [{ data: props.contentCharts?.types?.data ?? [] }],
}));

const contentCategoriesChartData = computed(() => ({
    labels: props.contentCharts?.categories?.labels ?? [],
    datasets: [{ data: props.contentCharts?.categories?.data ?? [] }],
}));

const contentStylesChartData = computed(() => ({
    labels: props.contentCharts?.styles?.labels ?? [],
    datasets: [{ data: props.contentCharts?.styles?.data ?? [] }],
}));

const contentStatusesChartData = computed(() => ({
    labels: props.contentCharts?.statuses?.labels ?? [],
    datasets: [{
        backgroundColor: ['#ef4444', '#3b82f6', '#eab308', '#16a34a', '#64748b', '#f97316'],
        data: props.contentCharts?.statuses?.data ?? [],
    }],
}));

const contentPlatformsChartData = computed(() => ({
    labels: props.contentCharts?.platforms?.labels ?? [],
    datasets: [{ backgroundColor: '#0ea5e9', data: props.contentCharts?.platforms?.data ?? [] }],
}));

const selectedContentChartTitle = computed(() => {
    return {
        line: 'Conteudos criados x publicados',
        types: 'Tipos de conteudo',
        categories: 'Categorias de conteudo',
        styles: 'Estilos de conteudo',
        statuses: 'Status dos conteudos',
        platforms: 'Plataformas de conteudo',
    }[selectedContentChart.value] ?? 'Graficos de conteudos';
});

const applyContentChartPeriod = () => {
    const payload = {
        ...localFilters,
        chart_period: selectedContentChartPeriod.value,
    };

    if (selectedContentChartPeriod.value === 'custom') {
        payload.chart_start = selectedContentChartStart.value || null;
        payload.chart_end = selectedContentChartEnd.value || null;
    } else {
        payload.chart_start = null;
        payload.chart_end = null;
    }

    router.get(route('contents.index'), payload, { preserveState: true, preserveScroll: true, replace: true });
};
</script>

<template>
    <div class="space-y-4">
        <BoPageHeader title="Conteúdos" subtitle="Planejamento e produção editorial" icon="mdi:film-reel">
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
                <Link :href="route('contents.create')">
                    <Button class="!hidden md:!inline-flex" icon="pi pi-plus" label="Novo conteúdo" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-plus" rounded aria-label="Novo conteúdo" />
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
                    <div class="flex items-center justify-center gap-1">
                        <iconify-icon :icon="slotProps.option.icon" width="16" height="16" />
                    </div>
                </template>
            </SelectButton>
        </div>

        <BoFilterBar :chips="filterChips" @submit="submitFilters" @reset="resetFilters" @remove-chip="removeChip" @cancel="cancelFilters">
            <div class="space-y-2">
                <label class="text-sm font-medium">Busca</label>
                <IconField>
                    <InputIcon class="pi pi-search" />
                    <InputText v-model="localFilters.search" placeholder="Buscar por título" />
                </IconField>
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Status</label>
                <Select
                    v-model="localFilters.status"
                    :options="[
                        { value: 'queued', label: 'Na fila' },
                        { value: 'in_production', label: 'Em produção' },
                        { value: 'finalized', label: 'Finalizado' },
                        { value: 'cancelled', label: 'Cancelado' },
                        { value: 'paused', label: 'Pausado' },
                        { value: 'published', label: 'Publicado' },
                    ]"
                    option-label="label"
                    option-value="value"
                    placeholder="Todos os status"
                    show-clear
                />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Plataforma</label>
                <Select v-model="localFilters.content_platform_id" :options="platforms" option-label="name" option-value="id" placeholder="Todas as plataformas" show-clear />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Tipo</label>
                <Select v-model="localFilters.idea_type_id" :options="types" option-label="name" option-value="id" placeholder="Todos os tipos" show-clear />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Categoria</label>
                <Select v-model="localFilters.idea_category_id" :options="categories" option-label="name" option-value="id" placeholder="Todas as categorias" show-clear />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Estilo</label>
                <Select v-model="localFilters.venue_style_id" :options="styles" option-label="name" option-value="id" placeholder="Todos os estilos" show-clear />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Semana</label>
                <InputText v-model="localFilters.planned_week" placeholder="Ex: 2026-W15" />
            </div>
        </BoFilterBar>

        <div class="hidden md:block">
            <Card v-if="viewMode === 'list'">
                <template #content>
                    <DataTable :value="contents.data" data-key="id" striped-rows :sort-mode="'single'" removable-sort>
                    <Column field="title" header="Título" sortable>
                        <template #body="{ data }">
                            <Link :href="route('contents.show', data.id)" class="font-medium hover:underline">{{ data.title }}</Link>
                        </template>
                    </Column>
                    <Column field="type.name" header="Tipo" sortable />
                    <Column header="Categorias">
                        <template #body="{ data }">
                            <div class="flex justify-center gap-1">
                                <Tag
                                    v-for="category in data.categories || []"
                                    :key="category.id"
                                    severity="secondary"
                                    class="!px-1.5 !py-0.5"
                                >
                                    <template #default>
                                        <iconify-icon :icon="category.icon || 'mdi:shape-outline'" width="14" height="14" v-tooltip.top="category.name" />
                                    </template>
                                </Tag>
                                <span v-if="!(data.categories || []).length" class="text-xs text-slate-400">-</span>
                            </div>
                        </template>
                    </Column>
                    <Column header="Estilos">
                        <template #body="{ data }">
                            <div class="flex justify-center gap-1">
                                <Tag
                                    v-for="style in data.styles || []"
                                    :key="style.id"
                                    severity="secondary"
                                    class="!px-1.5 !py-0.5"
                                >
                                    <template #default>
                                        <iconify-icon :icon="style.icon || 'mdi:palette-outline'" width="14" height="14" v-tooltip.top="style.name" />
                                    </template>
                                </Tag>
                                <span v-if="!(data.styles || []).length" class="text-xs text-slate-400">-</span>
                            </div>
                        </template>
                    </Column>
                    <Column header="Plataformas">
                        <template #body="{ data }">
                            <div class="flex justify-center gap-1">
                                <Tag
                                    v-for="platform in data.platforms"
                                    :key="platform.id"
                                    severity="secondary"
                                    class="!px-1.5 !py-0.5"
                                >
                                    <template #default>
                                        <iconify-icon :icon="platform.icon || 'mdi:play-network-outline'" width="14" height="14" v-tooltip.top="platform.name" />
                                    </template>
                                </Tag>
                                <span v-if="!(data.platforms || []).length" class="text-xs text-slate-400">-</span>
                            </div>
                        </template>
                    </Column>
                    <Column header="Status" sort-field="status" sortable>
                        <template #body="{ data }">
                            <BoStatusTag :value="data.status" />
                        </template>
                    </Column>
                    <Column header="Publicação" sort-field="planned_publish_at" sortable>
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <Tag :severity="publishMeta(data).severity" class="!px-1.5 !py-0.5">
                                    <template #default>
                                        <iconify-icon :icon="publishMeta(data).icon" width="12" height="12" />
                                    </template>
                                </Tag>
                                <BoDateText :value="publishMeta(data).value" mode="datetime" />
                            </div>
                        </template>
                    </Column>
                    <Column header="Ações" class="bo-action-col w-28">
                        <template #body="{ data }">
                            <div class="flex gap-1">
                                <Link :href="route('contents.show', data.id)">
                                    <Button icon="pi pi-eye" size="small" outlined rounded severity="secondary" v-tooltip.top="'Abrir'" />
                                </Link>
                                <Link :href="route('contents.edit', data.id)">
                                    <Button icon="pi pi-pencil" size="small" outlined rounded severity="secondary" v-tooltip.top="'Editar'" />
                                </Link>
                                <BoConfirmButton icon="pi pi-trash" severity="danger" message="Deseja remover este conteúdo?" :rounded="true" @confirm="removeContent(data.id)" />
                            </div>
                        </template>
                    </Column>
                    <template #empty>
                        <BoDataTableEmpty />
                    </template>
                    </DataTable>

                    <Paginator
                        class="mt-4"
                        :rows="contents.per_page"
                        :total-records="contents.total"
                        :first="(contents.current_page - 1) * contents.per_page"
                        @page="paginate"
                    />
                </template>
            </Card>

            <div v-else-if="viewMode === 'calendar'" class="hidden gap-3 md:grid md:grid-cols-2 xl:grid-cols-4">
                <Card v-for="column in calendarColumns" :key="column.label" class="lg:col-span-1">
                    <template #title>
                        <div class="flex items-center gap-2">
                            <span>{{ column.label }}</span>
                            <small class="rounded-full border border-slate-300 px-1.5 py-0.5 text-[10px] font-medium text-slate-600 dark:border-slate-600 dark:text-slate-300">
                                {{ formatWeekDate(column.date) }}
                            </small>
                        </div>
                    </template>
                    <template #content>
                        <div class="space-y-2">
                            <div v-if="!column.items.length" class="rounded border border-dashed border-slate-300 p-3 text-xs text-slate-500 dark:border-slate-700 dark:text-slate-400">
                                Sem publicações.
                            </div>
                            <Link
                                v-for="content in column.items"
                                :key="content.id"
                                :href="route('contents.show', content.id)"
                                class="block rounded-xl border border-slate-200 p-3 transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800"
                            >
                                <div class="grid grid-cols-[minmax(0,1fr)_auto] gap-2">
                                    <p class="line-clamp-2 text-xs font-semibold leading-4">{{ content.title }}</p>
                                    <div class="flex min-w-[7.5rem] flex-col items-end gap-0.5">
                                        <BoStatusTag :value="content.status" class="bo-weekly-status-tag" />
                                        <small class="text-[10px] text-slate-500">
                                            <BoDateText :value="contentWeekDate(content)" mode="datetime" />
                                        </small>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </template>
                </Card>
            </div>

            <Card v-else-if="viewMode === 'full_calendar'">
                <template #content>
                    <div class="mb-4 flex flex-wrap gap-2">
                        <div
                            v-for="legend in contentCalendarLegend"
                            :key="legend.key"
                            class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-3 py-1 text-xs font-medium text-slate-700 dark:border-slate-700 dark:text-slate-200"
                        >
                            <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: legend.color }" />
                            {{ legend.label }}
                        </div>
                    </div>
                    <FullCalendar :options="fullCalendarOptions" />
                </template>
            </Card>

        </div>

        <div v-if="viewMode === 'list'" class="block space-y-3 md:hidden">
            <div v-for="content in contents.data" :key="content.id" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="grid grid-cols-5 gap-3">
                    <div class="col-span-3 space-y-2">
                        <Link :href="route('contents.show', content.id)" class="block text-base font-semibold leading-5 hover:underline">{{ content.title }}</Link>

                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-slate-500">Tipo</p>
                            <p class="text-sm text-slate-700 dark:text-slate-200">{{ content.type?.name || '-' }}</p>
                        </div>

                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-slate-500">Categorias</p>
                            <div class="mt-1 flex flex-wrap gap-1">
                                <Tag v-for="category in content.categories || []" :key="category.id" severity="secondary" class="!px-1.5 !py-0.5">
                                    <template #default>
                                        <iconify-icon :icon="category.icon || 'mdi:shape-outline'" width="14" height="14" />
                                    </template>
                                </Tag>
                                <span v-if="!(content.categories || []).length" class="text-xs text-slate-400">-</span>
                            </div>
                        </div>

                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-slate-500">Estilos</p>
                            <div class="mt-1 flex flex-wrap gap-1">
                                <Tag v-for="style in content.styles || []" :key="style.id" severity="secondary" class="!px-1.5 !py-0.5">
                                    <template #default>
                                        <iconify-icon :icon="style.icon || 'mdi:palette-outline'" width="14" height="14" />
                                    </template>
                                </Tag>
                                <span v-if="!(content.styles || []).length" class="text-xs text-slate-400">-</span>
                            </div>
                        </div>

                        <div>
                            <p class="text-[11px] uppercase tracking-wide text-slate-500">Publicação</p>
                            <p class="text-xs text-slate-600 dark:text-slate-300"><BoDateText :value="publishMeta(content).value" mode="datetime" /></p>
                        </div>
                    </div>

                    <div class="col-span-2 flex flex-col items-end justify-between gap-3">
                        <div class="flex flex-col items-end gap-1">
                            <BoStatusTag :value="content.status" />
                            <Tag :severity="publishMeta(content).severity" class="!px-1.5 !py-0.5">
                                <template #default>
                                    <iconify-icon :icon="publishMeta(content).icon" width="12" height="12" />
                                </template>
                            </Tag>
                        </div>

                        <div class="flex flex-wrap justify-end gap-1">
                            <Link :href="route('contents.edit', content.id)"><Button icon="pi pi-pencil" size="small" outlined rounded severity="secondary" /></Link>
                            <BoConfirmButton icon="pi pi-trash" severity="danger" message="Deseja remover este conteúdo?" :rounded="true" @confirm="removeContent(content.id)" />
                        </div>
                    </div>
                </div>
            </div>

            <Paginator
                class="mt-4"
                :rows="contents.per_page"
                :total-records="contents.total"
                :first="(contents.current_page - 1) * contents.per_page"
                @page="paginate"
            />
        </div>

        <div v-else-if="viewMode === 'calendar'" class="md:hidden">
            <div class="space-y-2">
                <details
                    v-for="column in calendarColumns"
                    :key="column.label"
                    :open="isWeeklyColumnOpenByDefault(column)"
                    class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900"
                >
                    <summary class="cursor-pointer px-3 py-2 text-sm font-semibold">
                        <div class="inline-flex items-center gap-2">
                            <span>{{ column.label }}</span>
                            <small class="rounded-full border border-slate-300 px-1.5 py-0.5 text-[10px] font-medium text-slate-600 dark:border-slate-600 dark:text-slate-300">
                                {{ formatWeekDate(column.date) }}
                            </small>
                        </div>
                    </summary>
                    <div class="space-y-2 border-t border-slate-100 px-3 py-3 dark:border-slate-800">
                        <div v-if="!column.items.length" class="rounded border border-dashed border-slate-300 p-2 text-xs text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            Sem publicações.
                        </div>
                        <Link
                            v-for="content in column.items"
                            :key="content.id"
                            :href="route('contents.show', content.id)"
                            class="block rounded-lg border border-slate-200 p-2 dark:border-slate-700"
                        >
                            <div class="grid grid-cols-[minmax(0,1fr)_auto] gap-2">
                                <p class="line-clamp-2 text-xs font-semibold leading-4">
                                    <iconify-icon icon="mdi:film-reel" width="12" height="12" class="mr-1 align-[-2px]" />
                                    {{ content.title }}
                                </p>
                                <div class="flex min-w-[7.5rem] flex-col items-end gap-0.5">
                                    <BoStatusTag :value="content.status" class="bo-weekly-status-tag" />
                                    <small class="text-[10px] text-slate-500">
                                        <BoDateText :value="contentWeekDate(content)" mode="datetime" />
                                    </small>
                                </div>
                            </div>
                        </Link>
                    </div>
                </details>
            </div>
        </div>

        <Card v-else-if="viewMode === 'full_calendar'" class="bo-content-mobile-calendar md:hidden">
            <template #content>
                <div class="mb-4 flex flex-wrap gap-2">
                    <div
                        v-for="legend in contentCalendarLegend"
                        :key="legend.key"
                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-3 py-1 text-xs font-medium text-slate-700 dark:border-slate-700 dark:text-slate-200"
                    >
                        <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: legend.color }" />
                        {{ legend.label }}
                    </div>
                </div>
                <FullCalendar :options="fullCalendarOptions" />
            </template>
        </Card>

        <Card v-else>
            <template #title>
                <div class="space-y-3">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300">Graficos de conteudos</h3>
                    <div class="flex flex-wrap items-center gap-2">
                        <Select
                            v-model="selectedContentChart"
                            size="small"
                            :options="[
                                { label: 'Criados x publicados', value: 'line' },
                                { label: 'Tipos de conteúdo', value: 'types' },
                                { label: 'Categorias de conteúdo', value: 'categories' },
                                { label: 'Estilos de conteúdo', value: 'styles' },
                                { label: 'Status dos conteúdos', value: 'statuses' },
                                { label: 'Plataformas', value: 'platforms' },
                            ]"
                            option-label="label"
                            option-value="value"
                            class="w-full sm:w-48"
                        />
                        <Select
                            v-model="selectedContentChartPeriod"
                            size="small"
                            :options="[
                                { label: '7 dias', value: '7d' },
                                { label: '15 dias', value: '15d' },
                                { label: '30 dias', value: '30d' },
                                { label: 'Personalizado', value: 'custom' },
                            ]"
                            option-label="label"
                            option-value="value"
                            class="w-full sm:w-36"
                            @change="applyContentChartPeriod"
                        />
                    </div>
                </div>
            </template>
            <template #content>
                <div v-if="selectedContentChartPeriod === 'custom'" class="mb-4 flex flex-wrap items-end gap-2">
                    <div class="space-y-1">
                        <label class="text-xs font-medium">Início</label>
                        <InputText v-model="selectedContentChartStart" type="date" class="w-40" @change="applyContentChartPeriod" />
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-medium">Fim</label>
                        <InputText v-model="selectedContentChartEnd" type="date" class="w-40" @change="applyContentChartPeriod" />
                    </div>
                </div>

                <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ selectedContentChartTitle }}</p>

                <div class="h-[350px] md:h-[400px]">
                    <Chart v-if="selectedContentChart === 'line'" class="bo-chart-fill" type="line" :data="contentLineChartData" :options="chartOptions" />
                    <Chart v-else-if="selectedContentChart === 'types'" class="bo-chart-fill" type="doughnut" :data="contentTypesChartData" :options="doughnutChartOptions" />
                    <Chart v-else-if="selectedContentChart === 'categories'" class="bo-chart-fill" type="bar" :data="contentCategoriesChartData" :options="chartWithoutLegendOptions" />
                    <Chart v-else-if="selectedContentChart === 'styles'" class="bo-chart-fill" type="bar" :data="contentStylesChartData" :options="chartWithoutLegendOptions" />
                    <Chart v-else-if="selectedContentChart === 'statuses'" class="bo-chart-fill" type="doughnut" :data="contentStatusesChartData" :options="doughnutChartOptions" />
                    <Chart v-else class="bo-chart-fill" type="bar" :data="contentPlatformsChartData" :options="chartWithoutLegendOptions" />
                </div>
            </template>
        </Card>
    </div>
</template>
