<script setup>
import { computed, reactive } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFilterBar from '@/Components/ui/BoFilterBar.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoStatusTag from '@/Components/ui/BoStatusTag.vue';
import BoDataTableEmpty from '@/Components/ui/BoDataTableEmpty.vue';
import BoConfirmButton from '@/Components/ui/BoConfirmButton.vue';
import BoDateText from '@/Components/ui/BoDateText.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    ideas: Object,
    filters: Object,
    ideaTypes: Array,
    ideaCategories: Array,
});

const statusLabels = {
    pending: 'Pendente',
    maturing: 'Amadurecendo',
    cancelled: 'Cancelado',
    in_production: 'Em produção',
    executed: 'Executado',
};

const localFilters = reactive({
    status: props.filters?.status ?? null,
    idea_type_id: props.filters?.idea_type_id ?? null,
    idea_category_id: props.filters?.idea_category_id ?? null,
    search: props.filters?.search ?? '',
});

const filterChips = computed(() => {
    const chips = [];
    if (localFilters.search) chips.push({ key: 'search', label: localFilters.search });
    if (localFilters.status) chips.push({ key: 'status', label: statusLabels[localFilters.status] || localFilters.status });
    if (localFilters.idea_type_id) {
        const type = props.ideaTypes?.find((t) => t.id === localFilters.idea_type_id);
        if (type) chips.push({ key: 'idea_type_id', label: type.name });
    }
    if (localFilters.idea_category_id) {
        const cat = props.ideaCategories?.find((c) => c.id === localFilters.idea_category_id);
        if (cat) chips.push({ key: 'idea_category_id', label: cat.name });
    }
    return chips;
});

const submitFilters = () => {
    router.get(route('ideas.index'), localFilters, { preserveState: true, preserveScroll: true, replace: true });
};

const resetFilters = () => {
    localFilters.status = null;
    localFilters.idea_type_id = null;
    localFilters.idea_category_id = null;
    localFilters.search = '';
    submitFilters();
};

const removeChip = (key) => {
    localFilters[key] = key === 'search' ? '' : null;
    submitFilters();
};

const paginate = (event) => {
    router.get(route('ideas.index'), { ...localFilters, page: event.page + 1 }, { preserveState: true, preserveScroll: true, replace: true });
};

const executeIdea = (id) => router.post(route('ideas.execute', id), {}, { preserveScroll: true });
const removeIdea = (id) => router.delete(route('ideas.destroy', id), { preserveScroll: true });
</script>

<template>
    <div class="space-y-4">
        <BoPageHeader title="Ideias" subtitle="Painel de descoberta e execução de ideias">
            <template #actions>
                <Link :href="route('ideas.create')">
                    <Button icon="pi pi-plus" label="Nova ideia" />
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
                <label class="text-sm font-medium">Status</label>
                <Select
                    v-model="localFilters.status"
                    :options="[
                        { value: 'pending', label: 'Pendente' },
                        { value: 'maturing', label: 'Amadurecendo' },
                        { value: 'cancelled', label: 'Cancelado' },
                        { value: 'in_production', label: 'Em produção' },
                        { value: 'executed', label: 'Executado' },
                    ]"
                    option-label="label"
                    option-value="value"
                    placeholder="Todos os status"
                    show-clear
                />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Tipo</label>
                <Select v-model="localFilters.idea_type_id" :options="ideaTypes" option-label="name" option-value="id" placeholder="Todos os tipos" show-clear />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Categoria</label>
                <Select v-model="localFilters.idea_category_id" :options="ideaCategories" option-label="name" option-value="id" placeholder="Todas as categorias" show-clear />
            </div>
        </BoFilterBar>

        <Card>
            <template #content>
                <DataTable :value="ideas.data" data-key="id" striped-rows :sort-mode="'single'" removable-sort>
                    <Column field="title" header="Título" sortable />
                    <Column field="type.name" header="Tipo" sortable />
                    <Column field="category.name" header="Categoria" sortable />
                    <Column header="Status" sort-field="status" sortable>
                        <template #body="{ data }">
                            <BoStatusTag :value="data.status" />
                        </template>
                    </Column>
                    <Column field="user.name" header="Autor" sortable />
                    <Column header="Atualizado em" sort-field="updated_at" sortable>
                        <template #body="{ data }">
                            <BoDateText :value="data.updated_at" mode="datetime" />
                        </template>
                    </Column>
                    <Column header="Ações" class="bo-action-col w-32">
                        <template #body="{ data }">
                            <div class="flex gap-1">
                                <Button
                                    icon="pi pi-play"
                                    size="small"
                                    outlined
                                    rounded
                                    v-tooltip.top="'Executar'"
                                    @click="executeIdea(data.id)"
                                />
                                <Link :href="route('ideas.edit', data.id)">
                                    <Button icon="pi pi-pencil" size="small" outlined rounded severity="secondary" v-tooltip.top="'Editar'" />
                                </Link>
                                <BoConfirmButton icon="pi pi-trash" severity="danger" message="Deseja remover esta ideia?" :rounded="true" @confirm="removeIdea(data.id)" />
                            </div>
                        </template>
                    </Column>
                    <template #empty>
                        <BoDataTableEmpty />
                    </template>
                </DataTable>

                <Paginator
                    class="mt-4"
                    :rows="ideas.per_page"
                    :total-records="ideas.total"
                    :first="(ideas.current_page - 1) * ideas.per_page"
                    @page="paginate"
                />
            </template>
        </Card>
    </div>
</template>
