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
import AppKanbanCard from '@/Components/AppKanbanCard.vue';
import BoDateText from '@/Components/ui/BoDateText.vue';

defineOptions({ layout: AppLayout });
const props = defineProps({ tasks: Object, statuses: Array, contents: Array, filters: Object });

const viewMode = ref('list');

const priorityLabels = { low: 'Baixa', medium: 'Média', high: 'Alta', urgent: 'Urgente' };
const typeLabels = { content: 'Conteúdo', administrative: 'Administrativa' };

const localFilters = reactive({
    assignee: props.filters?.assignee ?? '',
    priority: props.filters?.priority ?? null,
    type: props.filters?.type ?? null,
    content_id: props.filters?.content_id ?? null,
    search: props.filters?.search ?? '',
});

const filterChips = computed(() => {
    const chips = [];
    if (localFilters.search) chips.push({ key: 'search', label: localFilters.search });
    if (localFilters.assignee) chips.push({ key: 'assignee', label: localFilters.assignee });
    if (localFilters.priority) chips.push({ key: 'priority', label: priorityLabels[localFilters.priority] || localFilters.priority });
    if (localFilters.type) chips.push({ key: 'type', label: typeLabels[localFilters.type] || localFilters.type });
    if (localFilters.content_id) {
        const content = props.contents?.find((c) => c.id === localFilters.content_id);
        if (content) chips.push({ key: 'content_id', label: content.title });
    }
    return chips;
});

const submitFilters = () => {
    router.get(route('tasks.index'), localFilters, { preserveState: true, preserveScroll: true, replace: true });
};

const resetFilters = () => {
    localFilters.assignee = '';
    localFilters.priority = null;
    localFilters.type = null;
    localFilters.content_id = null;
    localFilters.search = '';
    submitFilters();
};

const removeChip = (key) => {
    localFilters[key] = ['search', 'assignee'].includes(key) ? '' : null;
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

// --- WIP Limits (persisted in localStorage per column) ---
const WIP_KEY = 'bo_wip_limits';
const wipLimits = reactive(JSON.parse(localStorage.getItem(WIP_KEY) ?? '{}'));
const editingWip = ref(null);
const wipInputValue = ref(0);

const openWipEdit = (column) => {
    editingWip.value = column.id;
    wipInputValue.value = wipLimits[column.id] ?? 0;
};

const saveWipLimit = (columnId) => {
    const val = parseInt(wipInputValue.value);
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

// --- Swimlane: group tasks by assignee across status columns ---
const allAssignees = computed(() => {
    const seen = new Set();
    props.tasks.data.forEach((t) => seen.add(t.assignee ?? null));
    return [...seen].sort((a, b) => {
        if (!a) return 1;
        if (!b) return -1;
        return a.localeCompare(b);
    });
});

const swimlaneRows = computed(() =>
    allAssignees.value.map((assignee) => ({
        key: assignee ?? '__unassigned__',
        label: assignee ?? '(Sem responsável)',
        columns: kanbanColumns.value.map((col) => ({
            id: col.id,
            name: col.name,
            color: col.color,
            tasks: col.tasks.filter((t) => (t.assignee ?? null) === assignee),
        })),
    }))
);
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Tarefas" subtitle="Operacao em lista e kanban com drag and drop">
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
                <IconField>
                    <InputIcon class="pi pi-search" />
                    <InputText v-model="localFilters.search" placeholder="Buscar por título" />
                </IconField>
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Responsável</label>
                <InputText v-model="localFilters.assignee" placeholder="Nome do responsável" />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Prioridade</label>
                <Select
                    v-model="localFilters.priority"
                    :options="[
                        { value: 'low', label: 'Baixa' },
                        { value: 'medium', label: 'Média' },
                        { value: 'high', label: 'Alta' },
                        { value: 'urgent', label: 'Urgente' },
                    ]"
                    option-label="label"
                    option-value="value"
                    placeholder="Todas as prioridades"
                    show-clear
                />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Tipo</label>
                <Select
                    v-model="localFilters.type"
                    :options="[
                        { value: 'content', label: 'Conteúdo' },
                        { value: 'administrative', label: 'Administrativa' },
                    ]"
                    option-label="label"
                    option-value="value"
                    placeholder="Todos os tipos"
                    show-clear
                />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Conteúdo vinculado</label>
                <Select v-model="localFilters.content_id" :options="contents" option-label="title" option-value="id" placeholder="Todos os conteúdos" show-clear />
            </div>
        </BoFilterBar>

        <Card v-if="viewMode === 'list'">
            <template #content>
                <DataTable :value="tasks.data" data-key="id" striped-rows :sort-mode="'single'" removable-sort>
                    <Column field="title" header="Título" sortable />
                    <Column header="Tipo" sort-field="type" sortable>
                        <template #body="{ data }">{{ data.type === 'content' ? 'Conteúdo' : 'Administrativa' }}</template>
                    </Column>
                    <Column header="Prioridade" sort-field="priority" sortable>
                        <template #body="{ data }">
                            <BoPriorityTag :value="data.priority" />
                        </template>
                    </Column>
                    <Column header="Status" sort-field="status.name" sortable>
                        <template #body="{ data }">{{ data.status?.name || '-' }}</template>
                    </Column>
                    <Column field="user.name" header="Autor" sortable />
                    <Column field="assignee" header="Responsável" sortable />
                    <Column header="Prazo" sort-field="due_date" sortable>
                        <template #body="{ data }">
                            <BoDateText :value="data.due_date" mode="date" />
                        </template>
                    </Column>
                    <Column header="Ações" class="bo-action-col w-24">
                        <template #body="{ data }">
                            <div class="flex gap-1">
                                <Link :href="route('tasks.edit', data.id)">
                                    <Button icon="pi pi-pencil" size="small" outlined rounded severity="secondary" v-tooltip.top="'Editar'" />
                                </Link>
                                <BoConfirmButton icon="pi pi-trash" severity="danger" message="Deseja remover esta tarefa?" :rounded="true" @confirm="removeTask(data.id)" />
                            </div>
                        </template>
                    </Column>
                    <template #empty>
                        <BoDataTableEmpty />
                    </template>
                </DataTable>

                <Paginator
                    class="mt-4"
                    :rows="tasks.per_page"
                    :total-records="tasks.total"
                    :first="(tasks.current_page - 1) * tasks.per_page"
                    @page="paginate"
                />
            </template>
        </Card>

        <!-- Kanban view -->
        <div v-else-if="viewMode === 'kanban'" class="grid gap-4 xl:grid-cols-4">
            <Card v-for="column in kanbanColumns" :key="column.id" class="xl:col-span-1">
                <template #title>
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="inline-block h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: column.color || '#64748b' }" />
                            <span>{{ column.name }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <Tag
                                :value="laneMetrics(column).total"
                                :severity="isOverWip(column) ? 'danger' : 'secondary'"
                                class="cursor-pointer"
                                v-tooltip.top="wipLimits[column.id] ? `WIP: ${column.tasks.length}/${wipLimits[column.id]}` : 'Clique para definir limite WIP'"
                                @click="openWipEdit(column)"
                            />
                        </div>
                    </div>
                    <div v-if="editingWip === column.id" class="mt-2 flex items-center gap-2">
                        <InputNumber v-model="wipInputValue" :min="0" :max="99" input-class="w-16" size="small" />
                        <Button icon="pi pi-check" size="small" @click="saveWipLimit(column.id)" />
                        <Button icon="pi pi-times" size="small" severity="secondary" @click="editingWip = null" />
                    </div>
                    <div v-if="isOverWip(column)" class="mt-1 text-xs font-semibold text-red-500">
                        <i class="pi pi-exclamation-triangle mr-1" />Limite WIP excedido
                    </div>
                </template>
                <template #content>
                    <div class="mb-2 text-xs text-slate-500 dark:text-slate-400">
                        Urgentes: {{ laneMetrics(column).urgent }}
                    </div>

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

        <!-- Swimlane view -->
        <div v-else-if="viewMode === 'swimlane'" class="overflow-x-auto rounded-xl">
            <div
                class="bo-swimlane-grid min-w-max gap-3"
                :style="{ display: 'grid', gridTemplateColumns: `180px repeat(${kanbanColumns.length}, minmax(200px, 1fr))` }"
            >
                <!-- Header row -->
                <div class="rounded-lg bg-surface-100 px-3 py-2 text-xs font-bold uppercase tracking-wide text-surface-500 dark:bg-surface-800">
                    Responsável
                </div>
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
                    <div v-if="editingWip === col.id" class="absolute z-10 mt-1 flex items-center gap-1 rounded-lg bg-white p-2 shadow-lg dark:bg-surface-800">
                        <InputNumber v-model="wipInputValue" :min="0" :max="99" input-class="w-14" size="small" />
                        <Button icon="pi pi-check" size="small" @click="saveWipLimit(col.id)" />
                        <Button icon="pi pi-times" size="small" severity="secondary" @click="editingWip = null" />
                    </div>
                </div>

                <!-- Swimlane rows -->
                <template v-for="row in swimlaneRows" :key="row.key">
                    <!-- Assignee label cell -->
                    <div class="flex items-center rounded-lg border border-surface-200 bg-surface-50 px-3 py-2 text-sm font-medium text-surface-700 dark:border-surface-700 dark:bg-surface-900 dark:text-surface-200">
                        <i class="pi pi-user mr-2 text-surface-400" />
                        <span class="truncate">{{ row.label }}</span>
                        <Tag :value="row.columns.reduce((s, c) => s + c.tasks.length, 0)" severity="secondary" class="ml-auto" />
                    </div>

                    <!-- Task cells per status column -->
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
                            class="space-y-2 min-h-16"
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
                        <div v-if="cell.tasks.length === 0" class="flex h-12 items-center justify-center text-xs text-surface-400">
                            —
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>
