<script setup>
import { computed, reactive, ref } from 'vue';
import draggable from 'vuedraggable';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFilterBar from '@/Components/ui/BoFilterBar.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoDataTableEmpty from '@/Components/ui/BoDataTableEmpty.vue';
import BoPriorityTag from '@/Components/ui/BoPriorityTag.vue';
import BoConfirmButton from '@/Components/ui/BoConfirmButton.vue';
import BoDateText from '@/Components/ui/BoDateText.vue';
import AppKanbanCard from '@/Components/AppKanbanCard.vue';

defineOptions({ layout: AppLayout });
const props = defineProps({ tasks: Object, statuses: Array, contents: Array, plans: Array, users: Array, filters: Object });
const viewMode = ref('list');

const relatedTypeLabels = { content: 'Conteúdo', plan: 'Plano', administrative: 'Administrativo' };

const localFilters = reactive({
    assigned_user_id: props.filters?.assigned_user_id ?? null,
    priority: props.filters?.priority ?? null,
    related_type: props.filters?.related_type ?? null,
    content_id: props.filters?.content_id ?? null,
    search: props.filters?.search ?? '',
});

const filterChips = computed(() => {
    const chips = [];
    if (localFilters.search) chips.push({ key: 'search', label: localFilters.search });
    if (localFilters.related_type) chips.push({ key: 'related_type', label: relatedTypeLabels[localFilters.related_type] || localFilters.related_type });
    if (localFilters.priority) chips.push({ key: 'priority', label: localFilters.priority });
    if (localFilters.assigned_user_id) {
        const user = props.users.find((x) => x.id === localFilters.assigned_user_id);
        if (user) chips.push({ key: 'assigned_user_id', label: user.name });
    }
    return chips;
});

const submitFilters = () => {
    router.get(route('tasks.index'), localFilters, { preserveState: true, preserveScroll: true, replace: true });
};

const resetFilters = () => {
    localFilters.assigned_user_id = null;
    localFilters.priority = null;
    localFilters.related_type = null;
    localFilters.content_id = null;
    localFilters.search = '';
    submitFilters();
};

const removeChip = (key) => {
    localFilters[key] = key === 'search' ? '' : null;
    submitFilters();
};

const paginate = (event) => {
    router.get(route('tasks.index'), { ...localFilters, page: event.page + 1 }, { preserveState: true, preserveScroll: true, replace: true });
};

const removeTask = (taskId) => router.delete(route('tasks.destroy', taskId), { preserveScroll: true });

const kanbanColumns = ref(
    props.statuses.map((status) => ({
        id: status.id,
        name: status.name,
        color: status.color,
        tasks: props.tasks.data.filter((task) => task.task_status_id === status.id),
    })),
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
    for (const task of props.tasks.data) {
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
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Tarefas" subtitle="Operação de tarefas da banda">
            <template #actions>
                <SelectButton
                    v-model="viewMode"
                    size="small"
                    :options="[
                        { label: 'Lista', value: 'list' },
                        { label: 'Kanban', value: 'kanban' },
                        { label: 'Swimlane', value: 'swimlane' },
                    ]"
                    option-label="label"
                    option-value="value"
                />
                <Link :href="route('tasks.create')">
                    <Button icon="pi pi-plus" label="Nova tarefa" />
                </Link>
            </template>
        </BoPageHeader>

        <BoFilterBar :chips="filterChips" @submit="submitFilters" @reset="resetFilters" @remove-chip="removeChip">
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
                <Select v-model="localFilters.assigned_user_id" :options="users" option-label="name" option-value="id" show-clear placeholder="Todos" />
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
        </BoFilterBar>

        <Card v-if="viewMode === 'list'">
            <template #content>
                <div class="hidden md:block">
                    <DataTable :value="tasks.data" data-key="id" striped-rows>
                        <Column field="title" header="Título" />
                        <Column header="Relacionada a">
                            <template #body="{ data }">{{ relatedTypeLabels[data.related_type] || data.related_type }}</template>
                        </Column>
                        <Column header="Prioridade">
                            <template #body="{ data }">
                                <BoPriorityTag :value="data.priority" />
                            </template>
                        </Column>
                        <Column header="Status">
                            <template #body="{ data }">{{ data.status?.name || '-' }}</template>
                        </Column>
                        <Column header="Responsável">
                            <template #body="{ data }">{{ data.assigned_user?.name || data.assignedUser?.name || 'Todos' }}</template>
                        </Column>
                        <Column header="Prazo">
                            <template #body="{ data }">
                                <BoDateText :value="data.due_date" mode="date" />
                            </template>
                        </Column>
                        <Column header="Ações" class="bo-action-col w-24">
                            <template #body="{ data }">
                                <div class="flex gap-1">
                                    <Link :href="route('tasks.edit', data.id)">
                                        <Button icon="pi pi-pencil" size="small" outlined rounded severity="secondary" />
                                    </Link>
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
                    <div v-for="task in tasks.data" :key="task.id" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="mb-2 flex items-start justify-between gap-2">
                            <h3 class="font-semibold">{{ task.title }}</h3>
                            <BoPriorityTag :value="task.priority" />
                        </div>
                        <p class="text-xs text-slate-500">{{ relatedTypeLabels[task.related_type] || task.related_type }}</p>
                        <p class="text-xs text-slate-500">Status: {{ task.status?.name || '-' }}</p>
                        <p class="text-xs text-slate-500">Responsável: {{ task.assigned_user?.name || task.assignedUser?.name || 'Todos' }}</p>
                        <p class="text-xs text-slate-500">Prazo: <BoDateText :value="task.due_date" mode="date" /></p>
                        <div class="mt-3 flex justify-end gap-1">
                            <Link :href="route('tasks.edit', task.id)">
                                <Button icon="pi pi-pencil" size="small" outlined rounded severity="secondary" />
                            </Link>
                            <BoConfirmButton icon="pi pi-trash" severity="danger" message="Deseja remover esta tarefa?" :rounded="true" @confirm="removeTask(task.id)" />
                        </div>
                    </div>
                </div>

                <Paginator
                    class="mt-4"
                    :rows="tasks.per_page"
                    :total-records="tasks.total"
                    :first="(tasks.current_page - 1) * tasks.per_page"
                    @page="paginate"
                />
            </template>
        </Card>

        <div v-else-if="viewMode === 'kanban'" class="grid gap-4 xl:grid-cols-4">
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
                            <AppKanbanCard :task="element" />
                        </template>
                    </draggable>
                </template>
            </Card>
        </div>

        <div v-else-if="viewMode === 'swimlane'" class="overflow-x-auto rounded-xl">
            <div class="bo-swimlane-grid min-w-max gap-3" :style="{ display: 'grid', gridTemplateColumns: `180px repeat(${kanbanColumns.length}, minmax(200px, 1fr))` }">
                <div class="rounded-lg bg-surface-100 px-3 py-2 text-xs font-bold uppercase tracking-wide text-surface-500 dark:bg-surface-800">Responsável</div>
                <div
                    v-for="col in kanbanColumns"
                    :key="col.id"
                    class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold"
                    :class="isOverWip(col) ? 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300' : 'bg-surface-100 text-surface-700 dark:bg-surface-800 dark:text-surface-200'"
                >
                    <span class="inline-block h-2.5 w-2.5 rounded-full shrink-0" :style="{ backgroundColor: col.color || '#64748b' }" />
                    <span>{{ col.name }}</span>
                    <Tag
                        :value="col.tasks.length"
                        :severity="isOverWip(col) ? 'danger' : 'secondary'"
                        class="ml-auto cursor-pointer"
                        v-tooltip.top="wipLimits[col.id] ? `WIP: ${col.tasks.length}/${wipLimits[col.id]}` : 'Definir limite WIP'"
                        @click="openWipEdit(col)"
                    />
                </div>

                <template v-for="row in swimlaneRows" :key="row.key">
                    <div class="flex items-center rounded-lg border border-surface-200 bg-surface-50 px-3 py-2 text-sm font-medium text-surface-700 dark:border-surface-700 dark:bg-surface-900 dark:text-surface-200">
                        <i class="pi pi-user mr-2 text-surface-400" />
                        <span class="truncate">{{ row.label }}</span>
                        <Tag :value="row.columns.reduce((sum, c) => sum + c.tasks.length, 0)" severity="secondary" class="ml-auto" />
                    </div>

                    <div
                        v-for="cell in row.columns"
                        :key="cell.id"
                        class="min-h-24 rounded-lg border border-surface-200 p-1 dark:border-surface-700"
                        :class="cell.tasks.length === 0 ? 'bg-surface-50 dark:bg-surface-900' : 'bg-white dark:bg-surface-800'"
                    >
                        <draggable
                            :list="cell.tasks"
                            item-key="id"
                            group="tasks"
                            class="min-h-16 space-y-2"
                            :animation="180"
                            ghost-class="bo-kanban-ghost"
                            chosen-class="bo-kanban-chosen"
                            drag-class="bo-kanban-drag"
                            @change="(event) => onKanbanChange(cell.id, event)"
                        >
                            <template #item="{ element }">
                                <AppKanbanCard :task="element" />
                            </template>
                        </draggable>
                        <div v-if="cell.tasks.length === 0" class="flex h-12 items-center justify-center text-xs text-surface-400">-</div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>
