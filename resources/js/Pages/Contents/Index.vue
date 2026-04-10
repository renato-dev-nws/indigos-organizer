<script setup>
import { computed, reactive, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFilterBar from '@/Components/ui/BoFilterBar.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoStatusTag from '@/Components/ui/BoStatusTag.vue';
import BoDataTableEmpty from '@/Components/ui/BoDataTableEmpty.vue';
import BoConfirmButton from '@/Components/ui/BoConfirmButton.vue';

defineOptions({ layout: AppLayout });
const props = defineProps({ contents: Object, filters: Object, platforms: Array, types: Array, categories: Array });

const viewMode = ref('list');

const localFilters = reactive({
    status: props.filters?.status ?? null,
    content_platform_id: props.filters?.content_platform_id ?? null,
    content_type_id: props.filters?.content_type_id ?? null,
    content_category_id: props.filters?.content_category_id ?? null,
    planned_week: props.filters?.planned_week ?? '',
    search: props.filters?.search ?? '',
});

const submitFilters = () => {
    router.get(route('contents.index'), localFilters, { preserveState: true, preserveScroll: true, replace: true });
};

const resetFilters = () => {
    localFilters.status = null;
    localFilters.content_platform_id = null;
    localFilters.content_type_id = null;
    localFilters.content_category_id = null;
    localFilters.planned_week = '';
    localFilters.search = '';
    submitFilters();
};

const paginate = (event) => {
    router.get(route('contents.index'), { ...localFilters, page: event.page + 1 }, { preserveState: true, preserveScroll: true, replace: true });
};

const removeContent = (id) => router.delete(route('contents.destroy', id), { preserveScroll: true });

const calendarColumns = computed(() => {
    const weekDays = ['Segunda', 'Terca', 'Quarta', 'Quinta', 'Sexta', 'Sabado', 'Domingo'];
    const grouped = weekDays.map((label) => ({ label, items: [] }));

    for (const item of props.contents.data || []) {
        if (!item.planned_publish_at) {
            continue;
        }

        const date = new Date(item.planned_publish_at);
        const day = date.getDay();
        const index = day === 0 ? 6 : day - 1;
        grouped[index].items.push(item);
    }

    return grouped;
});
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Conteudos" subtitle="Planejamento e producao editorial">
            <template #actions>
                <SelectButton
                    v-model="viewMode"
                    size="small"
                    :options="[
                        { label: 'Lista', value: 'list' },
                        { label: 'Calendario', value: 'calendar' },
                    ]"
                    option-label="label"
                    option-value="value"
                />
                <Link :href="route('contents.create')">
                    <Button icon="pi pi-plus" label="Novo conteudo" />
                </Link>
            </template>
        </BoPageHeader>

        <BoFilterBar @submit="submitFilters" @reset="resetFilters">
            <IconField>
                <InputIcon class="pi pi-search" />
                <InputText v-model="localFilters.search" placeholder="Buscar por titulo" />
            </IconField>
            <Select v-model="localFilters.status" :options="['queued', 'in_production', 'cancelled', 'paused', 'published']" placeholder="Status" show-clear />
            <Select v-model="localFilters.content_platform_id" :options="platforms" option-label="name" option-value="id" placeholder="Plataforma" show-clear />
            <Select v-model="localFilters.content_type_id" :options="types" option-label="name" option-value="id" placeholder="Tipo" show-clear />
            <Select v-model="localFilters.content_category_id" :options="categories" option-label="name" option-value="id" placeholder="Categoria" show-clear />
            <InputText v-model="localFilters.planned_week" placeholder="Semana (YYYY-Www)" />
        </BoFilterBar>

        <Card v-if="viewMode === 'list'">
            <template #content>
                <DataTable :value="contents.data" data-key="id" responsive-layout="scroll" striped-rows>
                    <Column field="title" header="Titulo" />
                    <Column header="Plataforma">
                        <template #body="{ data }">{{ data.platform?.name || '-' }}</template>
                    </Column>
                    <Column header="Tipo">
                        <template #body="{ data }">{{ data.type?.name || '-' }}</template>
                    </Column>
                    <Column header="Status">
                        <template #body="{ data }">
                            <BoStatusTag :value="data.status" />
                        </template>
                    </Column>
                    <Column field="planned_publish_at" header="Publicacao" />
                    <Column header="Acoes" class="min-w-56">
                        <template #body="{ data }">
                            <div class="flex flex-wrap gap-2">
                                <Link :href="route('contents.show', data.id)">
                                    <Button icon="pi pi-eye" label="Abrir" size="small" outlined severity="secondary" />
                                </Link>
                                <Link :href="route('contents.edit', data.id)">
                                    <Button icon="pi pi-pencil" label="Editar" size="small" outlined severity="secondary" />
                                </Link>
                                <BoConfirmButton label="Excluir" icon="pi pi-trash" severity="danger" message="Deseja remover este conteudo?" @confirm="removeContent(data.id)" />
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

        <div v-else class="grid gap-4 lg:grid-cols-7">
            <Card v-for="column in calendarColumns" :key="column.label" class="lg:col-span-1">
                <template #title>{{ column.label }}</template>
                <template #content>
                    <div class="space-y-2">
                        <div v-if="!column.items.length" class="rounded border border-dashed border-slate-300 p-3 text-xs text-slate-500 dark:border-slate-700 dark:text-slate-400">
                            Sem publicacoes.
                        </div>
                        <div v-for="content in column.items" :key="content.id" class="rounded-xl border border-slate-200 p-3 dark:border-slate-800">
                            <p class="mb-2 text-sm font-semibold">{{ content.title }}</p>
                            <BoStatusTag :value="content.status" />
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </div>
</template>
