<script setup>
import { computed, reactive, ref, watch } from 'vue';
import draggable from 'vuedraggable';
import { router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import ptBrLocale from '@fullcalendar/core/locales/pt-br';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFilterBar from '@/Components/ui/BoFilterBar.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoDataTableEmpty from '@/Components/ui/BoDataTableEmpty.vue';
import BoPriorityTag from '@/Components/ui/BoPriorityTag.vue';
import BoTaskStatusTag from '@/Components/ui/BoTaskStatusTag.vue';
import BoConfirmButton from '@/Components/ui/BoConfirmButton.vue';
import BoDateText from '@/Components/ui/BoDateText.vue';
import AppKanbanCard from '@/Components/AppKanbanCard.vue';
import TaskFormModal from '@/Components/tasks/TaskFormModal.vue';
import TaskViewModal from '@/Components/tasks/TaskViewModal.vue';

defineOptions({ layout: AppLayout });
const props = defineProps({ tasks: Object, boardTasks: Array, taskCalendarItems: Array, weeklyTaskItems: Array, statuses: Array, contents: Array, plans: Array, events: Array, users: Array, filters: Object, currentUserId: String });
const confirm = useConfirm();
const viewMode = ref('list');
const tableRows = ref(15);
const tablePage = ref(0);

const relatedTypeLabels = { content: 'Conteúdo', plan: 'Plano', event: 'Evento', administrative: 'Administrativo' };

const normalizeAssignedUserFilter = (value) => {
    if (value === '__mine__' || value === '' || value === undefined || value === null) {
        return null;
    }

    return value;
};

const localFilters = reactive({
    assigned_user_id: normalizeAssignedUserFilter(props.filters?.assigned_user_id),
    priority: props.filters?.priority ?? null,
    related_type: props.filters?.related_type ?? null,
    content_id: props.filters?.content_id ?? null,
    include_archived: props.filters?.include_archived === '1' || props.filters?.include_archived === 1 || props.filters?.include_archived === true,
    search: props.filters?.search ?? '',
});

const syncLocalFiltersFromProps = () => {
    localFilters.assigned_user_id = normalizeAssignedUserFilter(props.filters?.assigned_user_id);
    localFilters.priority = props.filters?.priority ?? null;
    localFilters.related_type = props.filters?.related_type ?? null;
    localFilters.content_id = props.filters?.content_id ?? null;
    localFilters.include_archived = props.filters?.include_archived === '1' || props.filters?.include_archived === 1 || props.filters?.include_archived === true;
    localFilters.search = props.filters?.search ?? '';
};

const filterChips = computed(() => {
    const chips = [];
    if (localFilters.search) chips.push({ key: 'search', label: localFilters.search });
    if (localFilters.related_type) chips.push({ key: 'related_type', label: relatedTypeLabels[localFilters.related_type] || localFilters.related_type });
    if (localFilters.priority) chips.push({ key: 'priority', label: localFilters.priority });
    if (localFilters.include_archived) chips.push({ key: 'include_archived', label: 'Inclui arquivadas' });
    if (localFilters.assigned_user_id === '__all__') {
        chips.push({ key: 'assigned_user_id', label: 'Todos' });
    } else if (localFilters.assigned_user_id) {
        const user = props.users.find((x) => x.id === localFilters.assigned_user_id);
        if (user) chips.push({ key: 'assigned_user_id', label: user.name });
    }
    return chips;
});

const showMyTasksTag = computed(() => !localFilters.assigned_user_id);

const submitFilters = () => {
    tablePage.value = 0;
    router.get(route('tasks.index'), localFilters, { preserveState: true, preserveScroll: true, replace: true });
};

const resetFilters = () => {
    localFilters.assigned_user_id = null;
    localFilters.priority = null;
    localFilters.related_type = null;
    localFilters.content_id = null;
    localFilters.include_archived = false;
    localFilters.search = '';
    submitFilters();
};

const removeChip = (key) => {
    if (key === 'search') {
        localFilters.search = '';
    } else if (key === 'include_archived') {
        localFilters.include_archived = false;
    } else {
        localFilters[key] = null;
    }
    submitFilters();
};

const cancelFilters = () => {
    syncLocalFiltersFromProps();
};

watch(() => props.filters, syncLocalFiltersFromProps, { deep: true });

const paginate = (event) => {
    tablePage.value = event.page;
    tableRows.value = event.rows;
};

const removeTask = (taskId) => router.delete(route('tasks.destroy', taskId), { preserveScroll: true });
const showFormModal = ref(false);
const showViewModal = ref(false);
const selectedTask = ref(null);

const openCreateModal = () => {
    selectedTask.value = null;
    showFormModal.value = true;
};

const openEditModal = (task) => {
    selectedTask.value = task;
    showFormModal.value = true;
};

const openViewModal = (task) => {
    selectedTask.value = task;
    showViewModal.value = true;
};

const openViewModalByTaskId = async (taskId) => {
    if (!taskId) {
        return;
    }

    const existingTask = (props.boardTasks || props.tasks?.data || []).find((task) => task.id === taskId);
    if (existingTask) {
        openViewModal(existingTask);
        return;
    }

    const response = await fetch(route('tasks.show', taskId), {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });
    const payload = await response.json();

    if (payload?.task) {
        openViewModal(payload.task);
    }
};

const boardTasks = computed(() => props.boardTasks || props.tasks?.data || []);

const taskIsCompleted = (task) => {
    const statusName = String(task?.status?.name || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase();

    return ['conclu', 'finaliz', 'done', 'completed'].some((token) => statusName.includes(token));
};

const canShowTaskQuickAction = (task) => !(taskIsCompleted(task) && task.archived);

const quickActionMeta = (task) => {
    if (taskIsCompleted(task)) {
        return {
            action: 'archive',
            icon: 'mdi:archive-outline',
            severity: 'secondary',
            message: 'Deseja arquivar esta tarefa concluída?',
            tooltip: 'Arquivar tarefa',
        };
    }

    return {
        action: 'complete',
        icon: 'mdi:check',
        severity: 'success',
        message: 'Confirma concluir esta tarefa?',
        tooltip: 'Concluir tarefa',
    };
};

const runQuickAction = (task) => {
    const meta = quickActionMeta(task);

    confirm.require({
        header: 'Confirmar ação',
        message: meta.message,
        icon: 'pi pi-question-circle',
        rejectLabel: 'Cancelar',
        acceptLabel: 'Confirmar',
        acceptClass: 'p-button-sm',
        rejectClass: 'p-button-text p-button-sm',
        accept: () => {
            router.patch(route('tasks.quick-action', task.id), { action: meta.action }, { preserveScroll: true });
        },
    });
};

const paginatedTasks = computed(() => {
    const first = tablePage.value * tableRows.value;
    return boardTasks.value.slice(first, first + tableRows.value);
});

const buildKanbanColumns = () =>
    props.statuses.map((status) => ({
        id: status.id,
        name: status.name,
        color: status.color,
        tasks: boardTasks.value.filter((task) => task.task_status_id === status.id),
    }));

const kanbanColumns = ref(buildKanbanColumns());

watch(
    () => [props.statuses, boardTasks.value],
    () => {
        kanbanColumns.value = buildKanbanColumns();
        if (tablePage.value > 0 && tablePage.value * tableRows.value >= boardTasks.value.length) {
            tablePage.value = 0;
        }
    },
    { deep: true },
);

const laneMetrics = (column) => {
    const total = column.tasks.length;
    const urgent = column.tasks.filter((task) => task.priority === 'urgent').length;

    return { total, urgent };
};

const onKanbanChange = (statusId, event) => {
    const moved = event?.added?.element;
    if (!moved) {
        return;
    }

    const previousStatusId = moved.task_status_id;
    moved.task_status_id = statusId;

    router.patch(
        route('tasks.status', moved.id),
        { task_status_id: statusId },
        {
            preserveScroll: true,
            onError: () => {
                moved.task_status_id = previousStatusId;
            },
        },
    );
};

const isDragging = ref(false);

const WIP_KEY = 'bo_wip_limits';
const wipLimits = reactive(JSON.parse(localStorage.getItem(WIP_KEY) ?? '{}'));
const editingWip = ref(null);
const wipInputValue = ref(0);

const openWipEdit = (column) => {
    editingWip.value = column.id;
    wipInputValue.value = wipLimits[column.id] ?? 0;
};

const saveWipLimit = (columnId) => {
    const val = Number.parseInt(wipInputValue.value, 10);
    if (val > 0) {
        wipLimits[columnId] = val;
    } else {
        delete wipLimits[columnId];
    }
    localStorage.setItem(WIP_KEY, JSON.stringify({ ...wipLimits }));
    editingWip.value = null;
};

const isOverWip = (column) => {
    const limit = wipLimits[column.id];
    return limit > 0 && column.tasks.length > limit;
};

const taskAssigneeKey = (task) => task.assigned_user_id ?? task.assigned_user?.id ?? task.assignedUser?.id ?? null;
const taskAssigneeLabel = (task) => task.assigned_user?.name ?? task.assignedUser?.name ?? 'Todos';

const assignees = computed(() => {
    const map = new Map();
    for (const task of boardTasks.value) {
        const key = taskAssigneeKey(task);
        if (!map.has(key)) {
            map.set(key, taskAssigneeLabel(task));
        }
    }

    return [...map.entries()]
        .map(([id, name]) => ({ id, name }))
        .sort((a, b) => {
            if (a.id === null) return 1;
            if (b.id === null) return -1;
            return a.name.localeCompare(b.name);
        });
});

const swimlaneRows = computed(() =>
    assignees.value.map((assignee) => ({
        key: assignee.id ?? '__all__',
        label: assignee.name,
        columns: kanbanColumns.value.map((col) => ({
            id: col.id,
            name: col.name,
            color: col.color,
            tasks: col.tasks.filter((task) => taskAssigneeKey(task) === assignee.id),
        })),
    })),
);

const weekColumns = computed(() => {
    const labels = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
    const grouped = labels.map((label, dayIndex) => ({ label, dayIndex, items: [] }));

    for (const task of props.weeklyTaskItems || []) {
        const sourceDate = task.scheduled_for || task.due_date;
        if (!sourceDate) {
            continue;
        }

        const day = new Date(sourceDate).getDay();
        grouped[day].items.push(task);
    }

    return grouped;
});

const weekColumnsCarousel = computed(() => weekColumns.value.map((column) => ({ ...column })));

const taskWeekDate = (task) => task.scheduled_for || task.due_date;

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
    events: props.taskCalendarItems || [],
    eventClick: async (info) => {
        const taskId = info.event.extendedProps?.task_id;
        if (!taskId) {
            return;
        }

        info.jsEvent.preventDefault();
        await openViewModalByTaskId(taskId);
    },
}));
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Tarefas" subtitle="Operação de tarefas da banda" icon="ph:check-square-bold">
            <template #actions>
                <div class="hidden md:block">
                    <SelectButton
                        v-model="viewMode"
                        size="small"
                        :options="[
                            { label: 'Lista', value: 'list' },
                            { label: 'Kanban', value: 'kanban' },
                            { label: 'Programação da semana', value: 'weekly' },
                            { label: 'Calendário completo', value: 'full_calendar' },
                        ]"
                        option-label="label"
                        option-value="value"
                    />
                </div>
                <Button class="!hidden md:!inline-flex" icon="pi pi-plus" label="Nova tarefa" @click="openCreateModal" />
                <Button class="!inline-flex md:!hidden" icon="pi pi-plus" rounded aria-label="Nova tarefa" @click="openCreateModal" />
            </template>
        </BoPageHeader>

        <div class="md:hidden">
            <SelectButton
                v-model="viewMode"
                size="small"
                :options="[
                    { label: 'Lista', value: 'list' },
                    { label: 'Programação', value: 'weekly' },
                    { label: 'Calendário', value: 'full_calendar' },
                ]"
                option-label="label"
                option-value="value"
            />
        </div>

        <BoFilterBar :chips="filterChips" @submit="submitFilters" @reset="resetFilters" @remove-chip="removeChip" @cancel="cancelFilters">
            <template #after-filter-button>
                <Tag v-if="showMyTasksTag" value="Minhas tarefas" severity="secondary" />
            </template>
            <div class="space-y-2">
                <label class="text-sm font-medium">Busca</label>
                <InputText v-model="localFilters.search" placeholder="Título da tarefa" />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Relacionada a</label>
                <Select
                    v-model="localFilters.related_type"
                    :options="[
                        { label: 'Conteúdo', value: 'content' },
                        { label: 'Plano', value: 'plan' },
                        { label: 'Evento', value: 'event' },
                        { label: 'Administrativo', value: 'administrative' },
                    ]"
                    option-label="label"
                    option-value="value"
                    show-clear
                    placeholder="Todos"
                />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Responsável</label>
                <Select
                    v-model="localFilters.assigned_user_id"
                    :options="[{ id: '__all__', name: 'Todos' }, ...users]"
                    option-label="name"
                    option-value="id"
                    show-clear
                    placeholder="Minhas tarefas"
                />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Prioridade</label>
                <Select
                    v-model="localFilters.priority"
                    :options="[
                        { label: 'Baixa', value: 'low' },
                        { label: 'Média', value: 'medium' },
                        { label: 'Alta', value: 'high' },
                        { label: 'Urgente', value: 'urgent' },
                    ]"
                    option-label="label"
                    option-value="value"
                    show-clear
                    placeholder="Todas"
                />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Arquivadas</label>
                <div class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-700">
                    <Checkbox v-model="localFilters.include_archived" binary input-id="include-archived" />
                    <label for="include-archived" class="text-sm">Incluir tarefas arquivadas na lista</label>
                </div>
            </div>
        </BoFilterBar>

        <Card v-if="viewMode === 'list'">
            <template #content>
                <div class="hidden md:block">
                    <DataTable :value="paginatedTasks" data-key="id" striped-rows>
                        <Column field="title" header="Título">
                            <template #body="{ data }">
                                <button type="button" class="font-medium text-left hover:underline" @click="openViewModal(data)">{{ data.title }}</button>
                            </template>
                        </Column>
                        <Column header="Relacionada a">
                            <template #body="{ data }">{{ relatedTypeLabels[data.related_type] || data.related_type }}</template>
                        </Column>
                        <Column header="Responsável">
                            <template #body="{ data }">{{ data.assigned_user?.name || data.assignedUser?.name || 'Todos' }}</template>
                        </Column>
                        <Column header="Prioridade">
                            <template #body="{ data }">
                                <BoPriorityTag :value="data.priority" />
                            </template>
                        </Column>
                        <Column header="Agendado">
                            <template #body="{ data }">
                                <BoDateText :value="data.scheduled_for" mode="datetime" />
                            </template>
                        </Column>
                        <Column header="Prazo">
                            <template #body="{ data }">
                                <BoDateText :value="data.due_date" mode="date" />
                            </template>
                        </Column>
                        <Column header="Status">
                            <template #body="{ data }"><BoTaskStatusTag :status="data.status" /></template>
                        </Column>
                        <Column header="Ações" class="bo-action-col w-36">
                            <template #body="{ data }">
                                <div class="flex gap-1">
                                    <Button
                                        v-if="canShowTaskQuickAction(data)"
                                        outlined
                                        rounded
                                        size="small"
                                        :severity="quickActionMeta(data).severity"
                                        :aria-label="quickActionMeta(data).tooltip"
                                        v-tooltip.top="quickActionMeta(data).tooltip"
                                        @click="runQuickAction(data)"
                                    >
                                        <template #icon>
                                            <iconify-icon :icon="quickActionMeta(data).icon" width="14" height="14" />
                                        </template>
                                    </Button>
                                    <Button icon="pi pi-eye" size="small" outlined rounded severity="secondary" @click="openViewModal(data)" />
                                    <Button icon="pi pi-pencil" size="small" outlined rounded severity="secondary" @click="openEditModal(data)" />
                                    <BoConfirmButton icon="pi pi-trash" severity="danger" message="Deseja remover esta tarefa?" :rounded="true" @confirm="removeTask(data.id)" />
                                </div>
                            </template>
                        </Column>
                        <template #empty>
                            <BoDataTableEmpty />
                        </template>
                    </DataTable>
                </div>

                <div class="block space-y-3 md:hidden">
                    <div v-for="task in paginatedTasks" :key="task.id" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="mb-2 flex items-start justify-between gap-2">
                            <button type="button" class="font-semibold text-left hover:underline" @click="openViewModal(task)">{{ task.title }}</button>
                            <BoPriorityTag :value="task.priority" />
                        </div>
                        <p class="text-xs text-slate-500">{{ relatedTypeLabels[task.related_type] || task.related_type }}</p>
                        <div class="mt-1 flex items-center gap-2 text-xs text-slate-500">
                            <span>Status:</span>
                            <BoTaskStatusTag :status="task.status" />
                        </div>
                        <p class="text-xs text-slate-500">Responsável: {{ task.assigned_user?.name || task.assignedUser?.name || 'Todos' }}</p>
                        <p class="text-xs text-slate-500">Agendado: <BoDateText :value="task.scheduled_for" mode="datetime" /></p>
                        <p class="text-xs text-slate-500">Prazo: <BoDateText :value="task.due_date" mode="date" /></p>
                        <div class="mt-3 flex justify-end gap-1">
                            <Button
                                v-if="canShowTaskQuickAction(task)"
                                outlined
                                rounded
                                size="small"
                                :severity="quickActionMeta(task).severity"
                                :aria-label="quickActionMeta(task).tooltip"
                                v-tooltip.top="quickActionMeta(task).tooltip"
                                @click="runQuickAction(task)"
                            >
                                <template #icon>
                                    <iconify-icon :icon="quickActionMeta(task).icon" width="14" height="14" />
                                </template>
                            </Button>
                            <Button icon="pi pi-eye" size="small" outlined rounded severity="secondary" @click="openViewModal(task)" />
                            <Button icon="pi pi-pencil" size="small" outlined rounded severity="secondary" @click="openEditModal(task)" />
                            <BoConfirmButton icon="pi pi-trash" severity="danger" message="Deseja remover esta tarefa?" :rounded="true" @confirm="removeTask(task.id)" />
                        </div>
                    </div>
                </div>

                <Paginator
                    class="mt-4"
                    :rows="tableRows"
                    :total-records="boardTasks.length"
                    :first="tablePage * tableRows"
                    @page="paginate"
                />
            </template>
        </Card>

        <div v-else-if="viewMode === 'kanban'" class="hidden gap-4 md:grid xl:grid-cols-4">
            <Card v-for="column in kanbanColumns" :key="column.id" class="xl:col-span-1">
                <template #title>
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="inline-block h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: column.color || '#64748b' }" />
                            <span>{{ column.name }}</span>
                        </div>
                        <Tag
                            :value="laneMetrics(column).total"
                            :severity="isOverWip(column) ? 'danger' : 'secondary'"
                            class="cursor-pointer"
                            v-tooltip.top="wipLimits[column.id] ? `WIP: ${column.tasks.length}/${wipLimits[column.id]}` : 'Clique para definir limite WIP'"
                            @click="openWipEdit(column)"
                        />
                    </div>
                    <div v-if="editingWip === column.id" class="mt-2 flex items-center gap-2">
                        <InputNumber v-model="wipInputValue" :min="0" :max="99" input-class="w-16" size="small" />
                        <Button icon="pi pi-check" size="small" @click="saveWipLimit(column.id)" />
                        <Button icon="pi pi-times" size="small" severity="secondary" @click="editingWip = null" />
                    </div>
                </template>
                <template #content>
                    <div class="mb-2 text-xs text-slate-500 dark:text-slate-400">Urgentes: {{ laneMetrics(column).urgent }}</div>

                    <draggable
                        :list="column.tasks"
                        item-key="id"
                        group="tasks"
                        class="space-y-3 rounded-lg border border-transparent p-1 transition-colors"
                        :class="isDragging ? 'bo-kanban-drag-active' : ''"
                        :animation="180"
                        ghost-class="bo-kanban-ghost"
                        chosen-class="bo-kanban-chosen"
                        drag-class="bo-kanban-drag"
                        @start="isDragging = true"
                        @end="isDragging = false"
                        @change="(event) => onKanbanChange(column.id, event)"
                    >
                        <template #item="{ element }">
                            <AppKanbanCard :task="element" @view="openViewModal" @edit="openEditModal" />
                        </template>
                    </draggable>
                </template>
            </Card>
        </div>

        <template v-else-if="viewMode === 'weekly'">
            <Card class="mb-4">
                <template #title>Programação da semana</template>
                <template #content>
                    <div class="hidden gap-3 md:grid lg:grid-cols-7">
                        <Card v-for="column in weekColumns" :key="column.label" class="lg:col-span-1">
                            <template #title>{{ column.label }}</template>
                            <template #content>
                                <div class="space-y-2">
                                    <div v-if="!column.items.length" class="rounded border border-dashed border-slate-300 p-2 text-xs text-slate-500 dark:border-slate-700 dark:text-slate-400">
                                        Sem tarefas.
                                    </div>
                                    <button
                                        v-for="task in column.items"
                                        :key="task.id"
                                        type="button"
                                        class="w-full rounded-lg border border-slate-200 p-2 text-left hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800"
                                        @click="openViewModal(task)"
                                    >
                                        <p class="truncate text-xs font-semibold">
                                            <iconify-icon icon="ph:check-square-bold" width="12" height="12" class="mr-1 align-[-2px]" />
                                            {{ task.title }}
                                        </p>
                                        <div class="mt-1 flex items-center justify-between gap-1">
                                            <BoTaskStatusTag :status="task.status" />
                                            <span class="text-[10px] text-slate-500">
                                                <BoDateText :value="taskWeekDate(task)" mode="datetime" />
                                            </span>
                                        </div>
                                    </button>
                                </div>
                            </template>
                        </Card>
                    </div>

                    <div class="md:hidden">
                        <Carousel :value="weekColumnsCarousel" :num-visible="1" :num-scroll="1" :show-indicators="true" :show-navigators="false" circular>
                            <template #item="{ data: column }">
                                <div class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900">
                                    <p class="mb-2 text-sm font-semibold">{{ column.label }}</p>
                                    <div class="space-y-2">
                                        <div v-if="!column.items.length" class="rounded border border-dashed border-slate-300 p-2 text-xs text-slate-500 dark:border-slate-700 dark:text-slate-400">
                                            Sem tarefas.
                                        </div>
                                        <button
                                            v-for="task in column.items"
                                            :key="task.id"
                                            type="button"
                                            class="w-full rounded-lg border border-slate-200 p-2 text-left dark:border-slate-700"
                                            @click="openViewModal(task)"
                                        >
                                            <p class="truncate text-xs font-semibold">
                                                <iconify-icon icon="ph:check-square-bold" width="12" height="12" class="mr-1 align-[-2px]" />
                                                {{ task.title }}
                                            </p>
                                            <div class="mt-1 flex items-center justify-between gap-1">
                                                <BoTaskStatusTag :status="task.status" />
                                                <span class="text-[10px] text-slate-500">
                                                    <BoDateText :value="taskWeekDate(task)" mode="datetime" />
                                                </span>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </Carousel>
                    </div>
                </template>
            </Card>
        </template>

        <template v-else>
            <Card>
                <template #content>
                    <FullCalendar :options="fullCalendarOptions" />
                </template>
            </Card>
        </template>

        <TaskFormModal
            v-model:visible="showFormModal"
            :task="selectedTask"
            :statuses="statuses"
            :contents="contents"
            :plans="plans"
            :events="events"
            :users="users"
        />
        <TaskViewModal v-model:visible="showViewModal" :task="selectedTask" />
    </div>
</template>
