<script setup>
import { reactive, ref } from 'vue';
import draggable from 'vuedraggable';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFilterBar from '@/Components/ui/BoFilterBar.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoDataTableEmpty from '@/Components/ui/BoDataTableEmpty.vue';
import BoPriorityTag from '@/Components/ui/BoPriorityTag.vue';
import BoConfirmButton from '@/Components/ui/BoConfirmButton.vue';

defineOptions({ layout: AppLayout });
const props = defineProps({ tasks: Object, statuses: Array, contents: Array, filters: Object });

const viewMode = ref('list');

const localFilters = reactive({
    assignee: props.filters?.assignee ?? '',
    priority: props.filters?.priority ?? null,
    type: props.filters?.type ?? null,
    content_id: props.filters?.content_id ?? null,
    search: props.filters?.search ?? '',
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

const paginate = (event) => {
    router.get(route('tasks.index'), { ...localFilters, page: event.page + 1 }, { preserveState: true, preserveScroll: true, replace: true });
};

const removeTask = (taskId) => router.delete(route('tasks.destroy', taskId), { preserveScroll: true });

const kanbanColumns = ref(
    props.statuses.map((status) => ({
        id: status.id,
        name: status.name,
        tasks: props.tasks.data.filter((task) => task.task_status_id === status.id),
    })),
);

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
                    ]"
                    option-label="label"
                    option-value="value"
                />
                <Link :href="route('tasks.create')">
                    <Button icon="pi pi-plus" label="Nova tarefa" />
                </Link>
            </template>
        </BoPageHeader>

        <BoFilterBar @submit="submitFilters" @reset="resetFilters">
            <IconField>
                <InputIcon class="pi pi-search" />
                <InputText v-model="localFilters.search" placeholder="Buscar por titulo" />
            </IconField>
            <InputText v-model="localFilters.assignee" placeholder="Responsavel" />
            <Select v-model="localFilters.priority" :options="['low', 'medium', 'high', 'urgent']" placeholder="Prioridade" show-clear />
            <Select v-model="localFilters.type" :options="['content', 'administrative']" placeholder="Tipo" show-clear />
            <Select v-model="localFilters.content_id" :options="contents" option-label="title" option-value="id" placeholder="Conteudo" show-clear />
        </BoFilterBar>

        <Card v-if="viewMode === 'list'">
            <template #content>
                <DataTable :value="tasks.data" data-key="id" striped-rows responsive-layout="scroll">
                    <Column field="title" header="Titulo" />
                    <Column header="Tipo">
                        <template #body="{ data }">{{ data.type === 'content' ? 'Conteudo' : 'Administrativa' }}</template>
                    </Column>
                    <Column header="Prioridade">
                        <template #body="{ data }">
                            <BoPriorityTag :value="data.priority" />
                        </template>
                    </Column>
                    <Column header="Status">
                        <template #body="{ data }">{{ data.status?.name || '-' }}</template>
                    </Column>
                    <Column field="assignee" header="Responsavel" />
                    <Column field="due_date" header="Prazo" />
                    <Column header="Acoes" class="min-w-56">
                        <template #body="{ data }">
                            <div class="flex flex-wrap gap-2">
                                <Link :href="route('tasks.edit', data.id)">
                                    <Button icon="pi pi-pencil" label="Editar" size="small" outlined severity="secondary" />
                                </Link>
                                <BoConfirmButton label="Excluir" icon="pi pi-trash" severity="danger" message="Deseja remover esta tarefa?" @confirm="removeTask(data.id)" />
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

        <div v-else class="grid gap-4 xl:grid-cols-4">
            <Card v-for="column in kanbanColumns" :key="column.id" class="xl:col-span-1">
                <template #title>{{ column.name }}</template>
                <template #content>
                    <draggable
                        :list="column.tasks"
                        item-key="id"
                        group="tasks"
                        class="space-y-3"
                        @change="(event) => onKanbanChange(column.id, event)"
                    >
                        <template #item="{ element }">
                            <div class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900">
                                <p class="mb-2 font-semibold">{{ element.title }}</p>
                                <div class="mb-2 flex items-center justify-between">
                                    <BoPriorityTag :value="element.priority" />
                                    <span class="text-xs text-slate-500 dark:text-slate-400">{{ element.assignee || 'Sem responsavel' }}</span>
                                </div>
                                <ProgressBar :value="Math.round(((element.subtasks?.filter((s) => s.completed).length || 0) / (element.subtasks?.length || 1)) * 100)" style="height: 0.4rem" />
                            </div>
                        </template>
                    </draggable>
                </template>
            </Card>
        </div>
    </div>
</template>
