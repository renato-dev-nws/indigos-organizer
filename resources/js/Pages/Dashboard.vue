<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoStatusTag from '@/Components/ui/BoStatusTag.vue';
import BoTaskStatusTag from '@/Components/ui/BoTaskStatusTag.vue';
import BoDateText from '@/Components/ui/BoDateText.vue';

defineOptions({ layout: AppLayout });
const props = defineProps({
    summary: Object,
    dashboardCharts: Object,
    dashboardChartPeriods: Object,
    boardIdeas: Array,
    nextTasks: Array,
    nextEvents: Array,
    nextContents: Array,
    weeklyProgramItems: Array,
});
const page = usePage();
const programFilter = ref('all');
const weeklyProgramPage = ref(0);

const vote = (ideaId, voteValue) => {
    router.post(route('ideas.vote', ideaId), { vote: voteValue }, { preserveScroll: true });
};

const dashboardCards = computed(() => [
    {
        key: 'tasks',
        title: 'Tarefas',
        icon: 'mdi:checkbox-multiple-outline',
        colors: 'bg-indigo-300 dark:bg-indigo-500',
        value: props.summary?.tasksTotal || 0,
        subItems: [
            { label: 'Agendadas', value: props.summary?.tasksScheduled || 0, href: route('tasks.index', { scheduled_only: 1 }) },
            { label: 'Suas tarefas', value: props.summary?.tasksMine || 0, href: route('tasks.index') },
            { label: 'Tarefas de todos', value: props.summary?.tasksEveryone || 0, href: route('tasks.index', { assigned_user_id: '__unassigned__' }) },
            { label: 'Atrasadas', value: props.summary?.tasksOverdue || 0, href: route('tasks.index', { overdue_only: 1 }) },
        ],
    },
    {
        key: 'contents',
        title: 'Conteúdos',
        icon: 'mdi:film-reel',
        colors: 'bg-purple-300 dark:bg-purple-600',
        value: props.summary?.contentsTotal || 0,
        subItems: [
            { label: 'Na fila', value: props.summary?.contentsQueued || 0, href: route('contents.index', { status: 'queued' }) },
            { label: 'Em produção', value: props.summary?.contentsInProduction || 0, href: route('contents.index', { status: 'in_production' }) },
            { label: 'Finalizados', value: props.summary?.contentsFinalized || 0, href: route('contents.index', { status: 'finalized' }) },
            { label: 'Publicados', value: props.summary?.contentsPublished || 0, href: route('contents.index', { status: 'published' }) },
        ],
    },
    {
        key: 'ideas',
        title: 'Ideias',
        icon: 'mdi:lightbulb-multiple-outline',
        colors: 'bg-violet-300 dark:bg-violet-600',
        value: props.summary?.ideasTotal || 0,
        subItems: [
            { label: 'Suas ideias', value: props.summary?.ideasMine || 0, href: route('ideas.index', { mine: 1 }) },
            { label: 'Na gaveta', value: props.summary?.ideasInDrawer || 0, href: route('ideas.index', { status: 'in_drawer' }) },
            { label: 'Na mesa', value: props.summary?.ideasOnTable || 0, href: route('ideas.index', { status: 'on_table' }) },
            { label: 'No quadro', value: props.summary?.ideasOnBoard || 0, href: route('ideas.index', { status: 'on_board' }) },
        ],
    },
]);

const generalCards = computed(() => [
    { title: 'Planejamentos em execução', value: props.summary?.plansRunning || 0, href: route('plans.index') },
    { title: 'Eventos', value: props.summary?.eventsActive || 0, href: route('events.index') },
    { title: 'Locais', value: props.summary?.venuesTotal || 0, href: route('venues.index') },
    { title: 'Contatos', value: props.summary?.contactsTotal || 0, href: route('contacts.index') },
]);

const mobileShortcuts = [
    { title: 'Tarefas', icon: 'pi pi-check-square', href: route('tasks.index') },
    { title: 'Conteúdos', icon: 'pi pi-video', href: route('contents.index') },
    { title: 'Ideias', icon: 'pi pi-lightbulb', href: route('ideas.index') },
    { title: 'Planejamentos', icon: 'pi pi-list-check', href: route('plans.index') },
    { title: 'Eventos', icon: 'pi pi-calendar-plus', href: route('events.index') },
    { title: 'Locais', icon: 'pi pi-map-marker', href: route('venues.index') },
    { title: 'Calendário', icon: 'pi pi-calendar', href: route('calendar.index') },
    { title: 'Informações úteis', icon: 'pi pi-info-circle', href: route('shared-infos.index') },
    { title: 'Contatos', icon: 'pi pi-address-book', href: route('contacts.index') },
];

const parseDashboardDate = (value) => {
    if (!value) {
        return null;
    }

    if (value instanceof Date) {
        return Number.isNaN(value.getTime()) ? null : value;
    }

    if (typeof value === 'string') {
        const dateOnlyMatch = value.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (dateOnlyMatch) {
            const [, year, month, day] = dateOnlyMatch;
            return new Date(Number(year), Number(month) - 1, Number(day));
        }
    }

    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
};

const weeklyProgramColumns = computed(() => {
    const labels = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Próximo domingo'];
    const now = new Date();
    const weekStart = new Date(now);
    weekStart.setDate(now.getDate() - now.getDay());
    weekStart.setHours(0, 0, 0, 0);

    const grouped = Array.from({ length: 8 }).map((_, index) => {
        const date = new Date(weekStart);
        date.setDate(weekStart.getDate() + index);

        return {
            label: labels[index],
            dayIndex: index,
            date,
            items: [],
        };
    });

    for (const item of props.weeklyProgramItems || []) {
        if (programFilter.value === 'tasks' && item.kind !== 'task') {
            continue;
        }
        if (programFilter.value === 'content' && item.kind !== 'content') {
            continue;
        }
        if (programFilter.value === 'events' && item.kind !== 'event') {
            continue;
        }

        const baseDate = item.kind === 'task'
            ? (item.scheduled_for || item.due_date)
            : item.kind === 'content'
                ? item.planned_publish_at
                : item.starts_at;

        if (!baseDate) {
            continue;
        }

        const itemDate = parseDashboardDate(baseDate);
        if (!itemDate) {
            continue;
        }

        const normalizedDate = new Date(itemDate);
        normalizedDate.setHours(0, 0, 0, 0);
        const diffDays = Math.round((normalizedDate.getTime() - weekStart.getTime()) / 86_400_000);

        if (diffDays >= 0 && diffDays <= 7) {
            grouped[diffDays].items.push(item);
        }
    }

    return grouped;
});

const weeklyProgramCarousel = computed(() => weeklyProgramColumns.value.map((column) => ({ ...column })));

const initialWeeklyProgramPage = () => {
    return new Date().getDay();
};

const clampWeeklyProgramPage = (value) => {
    const total = weeklyProgramCarousel.value.length;
    if (!total) {
        return 0;
    }

    return Math.min(Math.max(value, 0), total - 1);
};

const goWeeklyProgramPrev = () => {
    const total = weeklyProgramCarousel.value.length;
    if (!total) {
        return;
    }

    weeklyProgramPage.value = weeklyProgramPage.value <= 0 ? total - 1 : weeklyProgramPage.value - 1;
};

const goWeeklyProgramNext = () => {
    const total = weeklyProgramCarousel.value.length;
    if (!total) {
        return;
    }

    weeklyProgramPage.value = weeklyProgramPage.value >= total - 1 ? 0 : weeklyProgramPage.value + 1;
};

onMounted(() => {
    weeklyProgramPage.value = clampWeeklyProgramPage(initialWeeklyProgramPage());
});

watch(weeklyProgramCarousel, () => {
    weeklyProgramPage.value = clampWeeklyProgramPage(weeklyProgramPage.value);
});

watch(
    () => props.dashboardChartPeriods,
    (value) => {
        dashboardTaskByUserPeriod.value = value?.taskByUserStatus?.period ?? '7d';
        dashboardContentsLinePeriod.value = value?.contentsLine?.period ?? '7d';
    },
    { deep: true },
);

const programItemDate = (item) => item.kind === 'task'
    ? (item.scheduled_for || item.due_date)
    : item.kind === 'content'
        ? item.planned_publish_at
        : item.starts_at;

const programItemIcon = (item) => {
    if (item.kind === 'task') {
        return 'mdi:checkbox-multiple-outline';
    }

    if (item.kind === 'content') {
        return 'mdi:film-reel';
    }

    return 'ph:calendar-star-bold';
};

const isTaskProgramItem = (item) => item.kind === 'task';
const isContentProgramItem = (item) => item.kind === 'content';
const isEventProgramItem = (item) => item.kind === 'event';

const eventPresenceIcon = (mode) => mode === 'participant' ? 'mdi:microphone-variant' : 'mdi:seat';
const eventPresenceLabel = (mode) => mode === 'participant' ? 'Participante' : 'Público/Audiência';

const programItemAccentColor = (item) => {
    if (item.kind === 'task') {
        return '#6366f1';
    }

    if (item.kind === 'content') {
        return '#a855f7';
    }

    return '#f97316';
};

const formatWeekDate = (value) => {
    const date = parseDashboardDate(value);
    if (!date) {
        return '';
    }

    return date.toLocaleDateString('pt-BR');
};

const taskAssigneeLabel = (task) => {
    const assignees = task?.assigned_users || task?.assignedUsers || [];
    if (!assignees.length) {
        return 'Todos';
    }

    return assignees.map((user) => user.name).join(', ');
};

const programFilterOptions = [
    { label: 'Todos', value: 'all', icon: 'ph:circles-three-bold' },
    { label: 'Tarefas', value: 'tasks', icon: 'mdi:checkbox-multiple-outline' },
    { label: 'Conteúdo', value: 'content', icon: 'mdi:film-reel' },
    { label: 'Eventos', value: 'events', icon: 'ph:calendar-star-bold' },
];

const nextTaskDateMode = (task) => task?.date_type === 'scheduled' ? 'datetime' : 'date';
const nextTaskDateClass = (task) => task?.date_type === 'scheduled' ? 'text-blue-500' : 'text-red-500';
const taskDeadlineTooltip = (task) => {
    const dueDate = parseDashboardDate(task?.due_date);
    if (!dueDate) {
        return 'Deadline';
    }

    return `Deadline: ${dueDate.toLocaleDateString('pt-BR')}`;
};

const contentPlannedLate = (content) => {
    if (!content?.planned_publish_at || content?.status === 'published') {
        return false;
    }

    const planned = new Date(content.planned_publish_at);
    if (Number.isNaN(planned.getTime())) {
        return false;
    }

    return planned.getTime() < Date.now();
};

const dashboardTaskByUserPeriod = ref(props.dashboardChartPeriods?.taskByUserStatus?.period ?? '7d');
const dashboardContentsLinePeriod = ref(props.dashboardChartPeriods?.contentsLine?.period ?? '7d');
const selectedDashboardUser = ref('__all__');

const dashboardChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
};

const dashboardChartNoLegendOptions = {
    ...dashboardChartOptions,
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

const dashboardTaskStatusChartOptions = {
    ...dashboardChartNoLegendOptions,
    plugins: {
        legend: {
            display: true,
            position: 'top',
        },
    },
};

const dashboardStackedChartOptions = {
    ...dashboardChartOptions,
    scales: {
        x: { stacked: true },
        y: {
            stacked: true,
            beginAtZero: true,
            ticks: {
                stepSize: 1,
                precision: 0,
            },
        },
    },
};

const dashboardTaskStatusChartData = computed(() => ({
    labels: props.dashboardCharts?.taskStatus?.labels ?? [],
    datasets: [
        {
            label: 'Total por status',
            backgroundColor: props.dashboardCharts?.taskStatus?.colors ?? [],
            data: props.dashboardCharts?.taskStatus?.data ?? [],
        },
        {
            label: 'Atrasadas',
            backgroundColor: '#dc2626',
            data: props.dashboardCharts?.taskStatus?.overdue ?? [],
        },
    ],
}));

const dashboardTaskByUserChartData = computed(() => {
    const users = props.dashboardCharts?.taskByUserStatus?.users ?? [];
    const statuses = props.dashboardCharts?.taskByUserStatus?.statuses ?? [];

    let labels = users.map((user) => user.name);
    let datasets = statuses.map((status) => ({
        label: status.name,
        backgroundColor: status.color || '#64748b',
        data: status.values,
    }));

    if (selectedDashboardUser.value && selectedDashboardUser.value !== '__all__') {
        const userIndex = users.findIndex((user) => user.id === selectedDashboardUser.value);
        if (userIndex >= 0) {
            labels = [users[userIndex].name];
            datasets = statuses.map((status) => ({
                label: status.name,
                backgroundColor: status.color || '#64748b',
                data: [status.values[userIndex] ?? 0],
            }));
        }
    }

    return { labels, datasets };
});

const dashboardContentsLineChartData = computed(() => ({
    labels: props.dashboardCharts?.contentsLine?.labels ?? [],
    datasets: [
        {
            type: 'line',
            label: 'Criados',
            borderColor: '#2563eb',
            backgroundColor: '#2563eb',
            tension: 0.3,
            data: props.dashboardCharts?.contentsLine?.created ?? [],
        },
        {
            type: 'line',
            label: 'Publicados',
            borderColor: '#16a34a',
            backgroundColor: '#16a34a',
            tension: 0.3,
            data: props.dashboardCharts?.contentsLine?.published ?? [],
        },
    ],
}));

const dashboardContentStatusesChartData = computed(() => ({
    labels: props.dashboardCharts?.contentStatuses?.labels ?? [],
    datasets: [{ backgroundColor: ['#ef4444', '#3b82f6', '#eab308', '#16a34a', '#64748b', '#f97316'], data: props.dashboardCharts?.contentStatuses?.data ?? [] }],
}));

const applyDashboardTaskByUserPeriod = () => {
    router.get(route('dashboard'), {
        dashboard_tasks_users_period: dashboardTaskByUserPeriod.value,
        dashboard_contents_line_period: dashboardContentsLinePeriod.value,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const applyDashboardContentsLinePeriod = () => {
    router.get(route('dashboard'), {
        dashboard_tasks_users_period: dashboardTaskByUserPeriod.value,
        dashboard_contents_line_period: dashboardContentsLinePeriod.value,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

// Indicators: 4 slots on desktop (numVisible=4), 7 on mobile (numVisible=1)
// We compute how many visible slots exist based on viewport — use a JS-computed value
// based on the current numVisible of the carousel (derived from window breakpoints).
// Since we can't easily read that, we expose a computed indicator count reactively.
const windowWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1200);
if (typeof window !== 'undefined') {
    window.addEventListener('resize', () => { windowWidth.value = window.innerWidth; });
}
const numVisibleSlots = computed(() => {
    if (windowWidth.value <= 768) return 1;
    if (windowWidth.value <= 1024) return 2;
    if (windowWidth.value <= 1400) return 3;
    return 4;
});
const totalDays = computed(() => weeklyProgramCarousel.value.length);
// Number of "pages" = total - numVisible + 1 (carousel wraps)
const indicatorCount = computed(() => Math.max(1, totalDays.value - numVisibleSlots.value + 1));
const weeklyProgramPageIndicator = computed(() => Math.min(weeklyProgramPage.value, indicatorCount.value - 1));
const visibleIndicators = computed(() =>
    Array.from({ length: indicatorCount.value }, (_, i) => ({
        ariaLabel: weeklyProgramCarousel.value[i]?.label ?? `Página ${i + 1}`,
    }))
);

const goWeeklyProgramToIndicator = (index) => {
    weeklyProgramPage.value = Math.min(index, totalDays.value - 1);
};
</script>

<template>
    <div class="space-y-4 md:space-y-6">
        <div class="hidden md:block">
            <BoPageHeader title="Dashboard" subtitle="Resumo de como está sua oranização" icon="ph:squares-four-bold" />
        </div>

        <div class="block md:hidden">
            <BoPageHeader :title="`Olá, ${page.props.auth?.user?.name || ''}!`" subtitle="Organize sua arte aqui" icon="ph:sparkle-bold" />
        </div>

        <div class="hidden gap-3 md:grid md:grid-cols-3">
            <Card v-for="card in dashboardCards" :key="card.key" class="h-full">
                <template #content>
                    <p class="text-xs font-bold uppercase tracking-wide rounded-full p-1" :class="card.colors">
                        <iconify-icon :icon="card.icon" class="mx-2 -mb-[0.1rem]" />
                        {{ card.title }}
                    </p>
                    <p class="mt-1 text-3xl text-center font-semibold text-slate-900 dark:text-slate-100">
                        {{ card.value }}
                    </p>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <Link v-for="item in card.subItems" :key="item.label" :href="item.href" class="rounded-lg bg-slate-100/80 p-2 text-xs dark:bg-slate-800/70 border border-indigo-500 transition hover:bg-slate-200/80 dark:hover:bg-slate-700/70">
                            <p class="text-slate-500 dark:text-slate-300">{{ item.label }}</p>
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ item.value }}</p>
                        </Link>
                    </div>
                </template>
            </Card>
        </div>

        <div class="md:hidden">
            <Carousel :value="dashboardCards" :num-visible="1" :num-scroll="1" :show-indicators="true" :show-navigators="false" circular>
                <template #item="{ data }">
                    <Card>
                        <template #content>
                            <p class="text-xs uppercase tracking-wide rounded-full p-1" :class="data.colors">
                                <iconify-icon :icon="data.icon" class="mx-2 -mb-[0.1rem]" />
                                {{ data.title }}
                            </p>
                            <p class="mt-1 text-center text-3xl font-semibold text-slate-900 dark:text-slate-100">
                                {{ data.value }}
                            </p>
                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <Link v-for="item in data.subItems" :key="item.label" :href="item.href" class="rounded-lg bg-slate-100/80 p-2 text-xs dark:bg-slate-800/70 transition hover:bg-slate-200/80 dark:hover:bg-slate-700/70">
                                    <p class="text-slate-500 dark:text-slate-300 text-center">{{ item.label }}</p>
                                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-100 text-center">{{ item.value }}</p>
                                </Link>
                            </div>
                        </template>
                    </Card>
                </template>
            </Carousel>
        </div>

        <h2 class="md:hidden text-xl">Acesso rápido</h2>
        <div class="grid grid-cols-3 gap-2 md:hidden">
            <Link v-for="shortcut in mobileShortcuts" :key="shortcut.title" :href="shortcut.href" class="rounded-xl border border-slate-200 bg-white px-2 py-3 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <i :class="shortcut.icon" class="mb-1 text-base text-slate-600 dark:text-slate-200" />
                <p class="text-[11px] font-medium leading-tight">{{ shortcut.title }}</p>
            </Link>
        </div>

        <Card>
            <template #title>
                <div class="flex items-center justify-between gap-3">
                    <span>Programação da semana</span>
                    <!-- Desktop: label + ícone -->
                    <div class="hidden md:flex items-center gap-1 rounded-md border border-slate-200 p-0.5 dark:border-slate-700">
                        <button
                            v-for="opt in programFilterOptions"
                            :key="opt.value"
                            type="button"
                            class="flex items-center gap-1.5 rounded px-3 py-1 text-xs font-medium transition-colors"
                            :class="programFilter === opt.value ? 'bg-indigo-500 text-white' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'"
                            @click="programFilter = opt.value"
                        >
                            <iconify-icon :icon="opt.icon" width="13" height="13" />
                            {{ opt.label }}
                        </button>
                    </div>
                    <!-- Mobile: ícones apenas -->
                    <div class="flex md:hidden items-center gap-1 rounded-md border border-slate-200 p-0.5 dark:border-slate-700">
                        <button
                            v-for="opt in programFilterOptions"
                            :key="opt.value"
                            type="button"
                            class="flex items-center justify-center rounded p-1.5 transition-colors"
                            :class="programFilter === opt.value ? 'bg-indigo-500 text-white' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'"
                            :aria-label="opt.label"
                            v-tooltip.bottom="opt.label"
                            @click="programFilter = opt.value"
                        >
                            <iconify-icon :icon="opt.icon" width="16" height="16" />
                        </button>
                    </div>
                </div>
            </template>
            <template #content>
                <Carousel
                    :value="weeklyProgramCarousel"
                    :num-visible="4"
                    :num-scroll="1"
                    :page="weeklyProgramPage"
                    @update:page="(value) => weeklyProgramPage = value"
                    :show-indicators="false"
                    :show-navigators="false"
                    :responsive-options="[
                        { breakpoint: '1400px', numVisible: 3, numScroll: 1 },
                        { breakpoint: '1024px', numVisible: 2, numScroll: 1 },
                        { breakpoint: '768px', numVisible: 1, numScroll: 1 },
                    ]"
                    circular
                    class="bo-weekly-carousel"
                >
                    <template #item="{ data: column }">
                        <div class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900 mx-1">
                            <div class="mb-2 flex items-center gap-2 rounded-full bg-slate-300 px-2 py-1 dark:bg-slate-700">
                                <p class="text-sm font-semibold">{{ column.label }}</p>
                                <small class="rounded-full border border-slate-400/40 bg-white/60 px-1.5 py-0.5 text-[10px] font-medium text-slate-700 dark:border-slate-500/40 dark:bg-slate-800/70 dark:text-slate-200">
                                    {{ formatWeekDate(column.date) }}
                                </small>
                            </div>
                            <div class="space-y-2">
                                <div v-if="!column.items.length" class="rounded border border-dashed border-slate-300 p-2 text-xs text-slate-500 dark:border-slate-700 dark:text-slate-400">
                                    Sem itens.
                                </div>
                                <Link
                                    v-for="item in column.items"
                                    :key="`${item.kind}-${item.id}`"
                                    :href="item.url"
                                    class="block rounded-lg border border-slate-200 p-2 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800"
                                    :style="{ borderLeftWidth: '4px', borderLeftColor: programItemAccentColor(item) }"
                                >
                                    <div class="grid grid-cols-[minmax(0,1fr)_auto] gap-2">
                                        <p class="line-clamp-2 text-xs font-semibold leading-4">
                                            <iconify-icon :icon="programItemIcon(item)" width="12" height="12" class="mr-1 align-[-2px]" />
                                            {{ item.title }}
                                        </p>
                                        <div class="flex min-w-[7.5rem] flex-col items-end gap-0.5">
                                            <BoTaskStatusTag v-if="isTaskProgramItem(item)" :status="item.status" class="bo-weekly-status-tag" />
                                            <BoStatusTag v-else-if="isContentProgramItem(item)" :value="item.status" class="bo-weekly-status-tag" />
                                            <div v-else-if="isEventProgramItem(item)" class="flex items-center gap-1">
                                                <iconify-icon
                                                    :icon="eventPresenceIcon(item.attendance_mode)"
                                                    width="14"
                                                    height="14"
                                                    v-tooltip.top="eventPresenceLabel(item.attendance_mode)"
                                                />
                                                <iconify-icon
                                                    v-if="item.is_online"
                                                    icon="mdi:presentation-play"
                                                    width="14"
                                                    height="14"
                                                    class="text-emerald-500"
                                                    v-tooltip.top="'Evento online'"
                                                />
                                            </div>
                                            <small class="text-[10px] text-slate-500">
                                                <BoDateText :value="programItemDate(item)" mode="datetime" />
                                            </small>
                                        </div>
                                    </div>
                                </Link>
                            </div>
                        </div>
                    </template>
                </Carousel>

                <div class="mt-3 flex items-center justify-center gap-3">
                    <Button
                        icon="pi pi-chevron-left"
                        text
                        rounded
                        aria-label="Dia anterior"
                        @click="goWeeklyProgramPrev"
                    />

                    <div class="flex items-center gap-1.5">
                        <button
                            v-for="(column, index) in visibleIndicators"
                            :key="`weekly-indicator-${index}`"
                            type="button"
                            class="h-2 rounded-full transition-all duration-200"
                            :class="index === weeklyProgramPageIndicator ? 'w-4 bg-indigo-500' : 'w-2 bg-slate-300 dark:bg-slate-700'"
                            :aria-label="column.ariaLabel"
                            @click="goWeeklyProgramToIndicator(index)"
                        />
                    </div>

                    <Button
                        icon="pi pi-chevron-right"
                        text
                        rounded
                        aria-label="Próximo dia"
                        @click="goWeeklyProgramNext"
                    />
                </div>
            </template>
        </Card>

        <div class="hidden grid-cols-1 gap-4 md:grid lg:grid-cols-2">
            <Card>
                <template #title>
                    <div class="flex items-center justify-between gap-2">
                        <span>Status de tarefas</span>
                    </div>
                </template>
                <template #content>
                    <div class="h-[350px] md:h-[400px]">
                        <Chart class="bo-chart-fill" type="bar" :data="dashboardTaskStatusChartData" :options="dashboardTaskStatusChartOptions" />
                    </div>
                </template>
            </Card>

            <Card>
                <template #title>
                    <div class="flex items-center justify-between gap-2">
                        <span>Tarefas por usuários</span>
                        <div class="ml-auto flex items-center gap-2">
                            <Select
                                v-model="dashboardTaskByUserPeriod"
                                size="small"
                                :options="[
                                    { label: '7 dias', value: '7d' },
                                    { label: '15 dias', value: '15d' },
                                    { label: '30 dias', value: '30d' },
                                ]"
                                option-label="label"
                                option-value="value"
                                class="!w-28"
                                @change="applyDashboardTaskByUserPeriod"
                            />
                            <Select v-model="selectedDashboardUser" size="small" :options="dashboardCharts?.taskByUserStatus?.users || []" option-label="name" option-value="id" class="!w-36" />
                        </div>
                    </div>
                </template>
                <template #content>
                    <div class="h-[350px] md:h-[400px]">
                        <Chart class="bo-chart-fill" type="bar" :data="dashboardTaskByUserChartData" :options="dashboardStackedChartOptions" />
                    </div>
                </template>
            </Card>

            <Card>
                <template #title>
                    <div class="flex items-center justify-between gap-2">
                        <span>Conteúdos criados x publicados</span>
                        <Select
                            v-model="dashboardContentsLinePeriod"
                            size="small"
                            :options="[
                                { label: '7 dias', value: '7d' },
                                { label: '15 dias', value: '15d' },
                                { label: '30 dias', value: '30d' },
                            ]"
                            option-label="label"
                            option-value="value"
                            class="!w-28"
                            @change="applyDashboardContentsLinePeriod"
                        />
                    </div>
                </template>
                <template #content>
                    <div class="h-[350px] md:h-[400px]">
                        <Chart class="bo-chart-fill" type="line" :data="dashboardContentsLineChartData" :options="dashboardChartOptions" />
                    </div>
                </template>
            </Card>

            <Card>
                <template #title>Status dos conteúdos</template>
                <template #content>
                    <div class="h-[350px] md:h-[400px]">
                        <Chart class="bo-chart-fill" type="bar" :data="dashboardContentStatusesChartData" :options="dashboardChartNoLegendOptions" />
                    </div>
                </template>
            </Card>
        </div>

        <div class="md:hidden">
            <Carousel :value="[1, 2, 3, 4]" :num-visible="1" :num-scroll="1" :show-indicators="true" :show-navigators="false" circular>
                <template #item="{ data }">
                    <Card>
                        <template #content>
                            <div v-if="data === 1" class="space-y-3">
                                <p class="text-sm font-semibold">Status de tarefas</p>
                                <div class="h-[350px]"><Chart class="bo-chart-fill" type="bar" :data="dashboardTaskStatusChartData" :options="dashboardTaskStatusChartOptions" /></div>
                            </div>
                            <div v-else-if="data === 2" class="space-y-3">
                                <div class="flex flex-col gap-2">
                                    <p class="text-sm font-semibold">Tarefas por usuários</p>
                                    <Select v-model="dashboardTaskByUserPeriod" size="small" :options="[{ label: '7 dias', value: '7d' }, { label: '15 dias', value: '15d' }, { label: '30 dias', value: '30d' }]" option-label="label" option-value="value" class="w-full" @change="applyDashboardTaskByUserPeriod" />
                                </div>
                                <Select v-model="selectedDashboardUser" size="small" :options="dashboardCharts?.taskByUserStatus?.users || []" option-label="name" option-value="id" class="w-full" />
                                <div class="h-[350px]"><Chart class="bo-chart-fill" type="bar" :data="dashboardTaskByUserChartData" :options="dashboardStackedChartOptions" /></div>
                            </div>
                            <div v-else-if="data === 3" class="space-y-3">
                                <div class="flex flex-col gap-2">
                                    <p class="text-sm font-semibold">Conteúdos criados x publicados</p>
                                    <Select v-model="dashboardContentsLinePeriod" size="small" :options="[{ label: '7 dias', value: '7d' }, { label: '15 dias', value: '15d' }, { label: '30 dias', value: '30d' }]" option-label="label" option-value="value" class="w-full" @change="applyDashboardContentsLinePeriod" />
                                </div>
                                <div class="h-[350px]"><Chart class="bo-chart-fill" type="line" :data="dashboardContentsLineChartData" :options="dashboardChartOptions" /></div>
                            </div>
                            <div v-else class="space-y-3">
                                <p class="text-sm font-semibold">Status dos conteúdos</p>
                                <div class="h-[350px]"><Chart class="bo-chart-fill" type="bar" :data="dashboardContentStatusesChartData" :options="dashboardChartNoLegendOptions" /></div>
                            </div>
                        </template>
                    </Card>
                </template>
            </Carousel>
        </div>

        <hr class="md:hidden" />

        <div class="grid grid-cols-2 gap-3 md:grid-cols-2 xl:grid-cols-4">
            <Link v-for="card in generalCards" :key="card.title" :href="card.href">
                <Card class="h-full transition hover:-translate-y-0.5 hover:shadow-md">
                    <template #content>
                        <p class="text-xs uppercase tracking-wide text-slate-500">{{ card.title }}</p>
                        <p class="mt-1 text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ card.value }}</p>
                    </template>
                </Card>
            </Link>
        </div>


        <div class="hidden gap-4 xl:grid xl:grid-cols-2">
            <Card>
                <template #content>
                    <h3 class="mb-3 text-lg text-orange-700 dark:text-orange-300 font-semibold">
                        <iconify-icon icon="mdi:format-list-checks" class="me-1 -mb-[0.2rem]" />
                        Próximas Tarefas
                    </h3>
                    <hr class="-mt-2 mb-2" />
                    <DataTable :value="nextTasks" data-key="id" size="small" striped-rows class="text-sm">
                        <Column header="Título">
                            <template #body="{ data }">
                                <div class="inline-flex items-center gap-1.5">
                                    <Link :href="data.url" class="font-medium hover:underline">{{ data.title }}</Link>
                                    <iconify-icon
                                        v-if="data.is_deadline_soon"
                                        icon="mdi:skull"
                                        width="13"
                                        height="13"
                                        class="text-red-500"
                                        v-tooltip.top="taskDeadlineTooltip(data)"
                                    />
                                </div>
                            </template>
                        </Column>
                        <Column header="Responsável">
                            <template #body="{ data }">{{ taskAssigneeLabel(data) }}</template>
                        </Column>
                        <Column header="Data">
                            <template #body="{ data }">
                                <span :class="nextTaskDateClass(data)">
                                    <BoDateText :value="data.display_date" :mode="nextTaskDateMode(data)" />
                                </span>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <Card>
                <template #content>
                    <h3 class="mb-3 text-lg text-blue-700 dark:text-blue-300 font-semibold">
                        <iconify-icon icon="mdi:movie-open-star" class="me-1 -mb-[0.2rem]" />
                        Próximos Conteúdos
                    </h3>
                    <hr class="-mt-2 mb-2" />
                    <DataTable :value="nextContents" data-key="id" size="small" striped-rows class="text-sm">
                        <Column header="Título">
                            <template #body="{ data }">
                                <div class="inline-flex items-center gap-1.5">
                                    <Link :href="route('contents.show', data.id)" class="font-medium hover:underline">{{ data.title }}</Link>
                                    <iconify-icon
                                        v-if="contentPlannedLate(data)"
                                        icon="mdi:emoticon-sad"
                                        width="14"
                                        height="14"
                                        class="text-red-500"
                                        v-tooltip.top="'Publicação planejada ultrapassada'"
                                    />
                                </div>
                            </template>
                        </Column>
                        <Column header="Status"><template #body="{ data }"><BoStatusTag :value="data.status" /></template></Column>
                        <Column header="Publicação"><template #body="{ data }"><BoDateText :value="data.planned_publish_at" mode="datetime" /></template></Column>
                    </DataTable>
                </template>
            </Card>

            <Card>
                <template #content>
                    <h3 class="mb-3 text-lg text-emerald-700 dark:text-emerald-300 font-semibold">
                        <iconify-icon icon="mdi:event-clock" class="me-1 -mb-[0.2rem]" />
                        Próximos eventos
                    </h3>
                    <hr class="-mt-2 mb-2" />
                    <DataTable :value="nextEvents" data-key="id" size="small" striped-rows class="text-sm">
                        <Column header="Título">
                            <template #body="{ data }">
                                <Link :href="route('events.show', data.id)" class="font-medium hover:underline">{{ data.title }}</Link>
                            </template>
                        </Column>
                        <Column header="Tipo"><template #body="{ data }">{{ data.type?.name || '-' }}</template></Column>
                        <Column header="Data"><template #body="{ data }"><BoDateText :value="data.starts_at" mode="datetime" /></template></Column>
                        <Column header="Presença">
                            <template #body="{ data }">
                                <div class="inline-flex items-center gap-1.5">
                                    <iconify-icon
                                        :icon="eventPresenceIcon(data.attendance_mode)"
                                        width="15"
                                        height="15"
                                        v-tooltip.top="eventPresenceLabel(data.attendance_mode)"
                                    />
                                    <iconify-icon
                                        v-if="data.is_online"
                                        icon="mdi:presentation-play"
                                        width="15"
                                        height="15"
                                        class="text-green-500"
                                        v-tooltip.top="'Evento online'"
                                    />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <Card>
                <template #content>
                    <h3 class="mb-3 text-lg text-blue-700 dark:text-blue-300 font-semibold">
                        <iconify-icon icon="mdi:vote" class="me-1 -mb-[0.2rem]" />
                        Votação
                    </h3>
                    <hr class="-mt-2 mb-2" />
                    <div v-if="boardIdeas.length" class="space-y-3">
                        <div v-for="idea in boardIdeas" :key="idea.id" class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <div class="mb-2 flex items-start justify-between gap-2">
                                <Link :href="route('ideas.show', idea.id)" class="font-semibold hover:underline">{{ idea.title }}</Link>
                                <Tag value="No quadro" severity="warn" />
                            </div>
                            <p class="mb-3 text-sm text-slate-500">Criada por {{ idea.user?.name || '-' }}</p>
                            <div class="flex flex-wrap gap-2">
                                <Button size="small" label="Na mesa" @click="vote(idea.id, 'on_table')" />
                                <Button size="small" label="Na gaveta" severity="secondary" @click="vote(idea.id, 'in_drawer')" />
                                <Button size="small" label="No lixo" severity="danger" @click="vote(idea.id, 'trash')" />
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-slate-500">Nenhuma ideia pendente de voto para você.</p>
                </template>
            </Card>
        </div>

        <Card class="md:hidden">
            <template #title>Votação</template>
            <template #content>
                <div v-if="boardIdeas.length" class="space-y-3">
                    <div v-for="idea in boardIdeas" :key="idea.id" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="mb-2 flex items-start justify-between gap-2">
                            <Link :href="route('ideas.show', idea.id)" class="font-semibold hover:underline">{{ idea.title }}</Link>
                            <Tag value="No quadro" severity="warn" />
                        </div>
                        <p class="mb-3 text-sm text-slate-500">Criada por {{ idea.user?.name || '-' }}</p>
                        <div class="flex flex-wrap gap-2">
                            <Button size="small" label="Na mesa" @click="vote(idea.id, 'on_table')" />
                            <Button size="small" label="Na gaveta" severity="secondary" @click="vote(idea.id, 'in_drawer')" />
                            <Button size="small" label="No lixo" severity="danger" @click="vote(idea.id, 'trash')" />
                        </div>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-500">Nenhuma ideia pendente de voto para você.</p>
            </template>
        </Card>
    </div>
</template>
