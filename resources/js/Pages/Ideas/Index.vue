<script setup>
import { reactive } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFilterBar from '@/Components/ui/BoFilterBar.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoStatusTag from '@/Components/ui/BoStatusTag.vue';
import BoDataTableEmpty from '@/Components/ui/BoDataTableEmpty.vue';
import BoConfirmButton from '@/Components/ui/BoConfirmButton.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    ideas: Object,
    filters: Object,
    ideaTypes: Array,
    ideaCategories: Array,
});

const localFilters = reactive({
    status: props.filters?.status ?? null,
    idea_type_id: props.filters?.idea_type_id ?? null,
    idea_category_id: props.filters?.idea_category_id ?? null,
    search: props.filters?.search ?? '',
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

const paginate = (event) => {
    router.get(route('ideas.index'), { ...localFilters, page: event.page + 1 }, { preserveState: true, preserveScroll: true, replace: true });
};

const executeIdea = (id) => router.post(route('ideas.execute', id), {}, { preserveScroll: true });
const removeIdea = (id) => router.delete(route('ideas.destroy', id), { preserveScroll: true });
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Ideias" subtitle="Painel de descoberta e execucao de ideias">
            <template #actions>
                <Link :href="route('ideas.create')">
                    <Button icon="pi pi-plus" label="Nova ideia" />
                </Link>
            </template>
        </BoPageHeader>

        <BoFilterBar @submit="submitFilters" @reset="resetFilters">
            <IconField>
                <InputIcon class="pi pi-search" />
                <InputText v-model="localFilters.search" placeholder="Buscar por titulo" />
            </IconField>
            <Select v-model="localFilters.status" :options="['pending', 'maturing', 'cancelled', 'in_production', 'executed']" placeholder="Status" show-clear />
            <Select v-model="localFilters.idea_type_id" :options="ideaTypes" option-label="name" option-value="id" placeholder="Tipo" show-clear />
            <Select v-model="localFilters.idea_category_id" :options="ideaCategories" option-label="name" option-value="id" placeholder="Categoria" show-clear />
        </BoFilterBar>

        <Card>
            <template #content>
                <DataTable :value="ideas.data" data-key="id" responsive-layout="scroll" striped-rows>
                    <Column field="title" header="Titulo" />
                    <Column header="Tipo">
                        <template #body="{ data }">{{ data.type?.name || '-' }}</template>
                    </Column>
                    <Column header="Categoria">
                        <template #body="{ data }">{{ data.category?.name || '-' }}</template>
                    </Column>
                    <Column header="Status">
                        <template #body="{ data }">
                            <BoStatusTag :value="data.status" />
                        </template>
                    </Column>
                    <Column field="updated_at" header="Atualizado" />
                    <Column header="Acoes" class="min-w-48">
                        <template #body="{ data }">
                            <div class="flex flex-wrap gap-2">
                                <Button icon="pi pi-play" label="Executar" size="small" outlined @click="executeIdea(data.id)" />
                                <Link :href="route('ideas.edit', data.id)">
                                    <Button icon="pi pi-pencil" label="Editar" size="small" outlined severity="secondary" />
                                </Link>
                                <BoConfirmButton label="Excluir" icon="pi pi-trash" severity="danger" message="Deseja remover esta ideia?" @confirm="removeIdea(data.id)" />
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
