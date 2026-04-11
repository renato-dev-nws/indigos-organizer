<script setup>
import { computed, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFilterBar from '@/Components/ui/BoFilterBar.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoDataTableEmpty from '@/Components/ui/BoDataTableEmpty.vue';
import BoPriorityTag from '@/Components/ui/BoPriorityTag.vue';
import BoConfirmButton from '@/Components/ui/BoConfirmButton.vue';
import BoDateText from '@/Components/ui/BoDateText.vue';

defineOptions({ layout: AppLayout });
const props = defineProps({ tasks: Object, statuses: Array, contents: Array, plans: Array, users: Array, filters: Object });

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
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Tarefas" subtitle="Operação de tarefas da banda">
            <template #actions>
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

        <Card>
            <template #content>
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
                        <template #body="{ data }">{{ data.assigned_user?.name || 'Todos' }}</template>
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

                <Paginator
                    class="mt-4"
                    :rows="tasks.per_page"
                    :total-records="tasks.total"
                    :first="(tasks.current_page - 1) * tasks.per_page"
                    @page="paginate"
                />
            </template>
        </Card>
    </div>
</template>
