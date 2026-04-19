<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import draggable from 'vuedraggable';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFilterBar from '@/Components/ui/BoFilterBar.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoStatusTag from '@/Components/ui/BoStatusTag.vue';
import BoDataTableEmpty from '@/Components/ui/BoDataTableEmpty.vue';
import BoConfirmButton from '@/Components/ui/BoConfirmButton.vue';
import BoDateText from '@/Components/ui/BoDateText.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({ ideas: Object, ideaBoardItems: Array, filters: Object, ideaTypes: Array, ideaCategories: Array, venueStyles: Array });
const viewMode = ref('list');
let viewportQuery = null;

const statusLabels = {
    in_drawer: 'Na gaveta',
    on_table: 'Na mesa',
    on_board: 'No quadro',
    executing: 'Em execução',
    executed: 'Executada',
    trash: 'No lixo',
};

const localFilters = reactive({
    status: props.filters?.status ?? null,
    idea_type_id: props.filters?.idea_type_id ?? null,
    idea_category_id: props.filters?.idea_category_id ?? null,
    venue_style_id: props.filters?.venue_style_id ?? null,
    search: props.filters?.search ?? '',
});

const syncLocalFiltersFromProps = () => {
    localFilters.status = props.filters?.status ?? null;
    localFilters.idea_type_id = props.filters?.idea_type_id ?? null;
    localFilters.idea_category_id = props.filters?.idea_category_id ?? null;
    localFilters.venue_style_id = props.filters?.venue_style_id ?? null;
    localFilters.search = props.filters?.search ?? '';
};

const filterChips = computed(() => {
    const chips = [];
    if (localFilters.search) chips.push({ key: 'search', label: localFilters.search });
    if (localFilters.status) chips.push({ key: 'status', label: statusLabels[localFilters.status] || localFilters.status });
    if (localFilters.venue_style_id) {
        const style = props.venueStyles?.find((item) => item.id === localFilters.venue_style_id);
        if (style) chips.push({ key: 'venue_style_id', label: style.name });
    }
    return chips;
});

const submitFilters = () => router.get(route('ideas.index'), localFilters, { preserveState: true, preserveScroll: true, replace: true });
const resetFilters = () => {
    localFilters.status = null;
    localFilters.idea_type_id = null;
    localFilters.idea_category_id = null;
    localFilters.venue_style_id = null;
    localFilters.search = '';
    submitFilters();
};
const removeChip = (key) => {
    localFilters[key] = key === 'search' ? '' : null;
    submitFilters();
};

const cancelFilters = () => {
    syncLocalFiltersFromProps();
};

watch(() => props.filters, syncLocalFiltersFromProps, { deep: true });
const paginate = (event) => router.get(route('ideas.index'), { ...localFilters, page: event.page + 1 }, { preserveState: true, preserveScroll: true, replace: true });
const removeIdea = (id) => router.delete(route('ideas.destroy', id), { preserveScroll: true });

const orderedKanbanStatus = ['in_drawer', 'on_table', 'on_board', 'executing', 'executed'];
const buildKanbanColumns = () =>
    orderedKanbanStatus.map((status) => ({
        status,
        label: statusLabels[status],
        items: (props.ideaBoardItems || []).filter((idea) => idea.status === status),
    }));

const kanbanColumns = ref(buildKanbanColumns());

watch(
    () => props.ideaBoardItems,
    () => {
        kanbanColumns.value = buildKanbanColumns();
    },
    { deep: true },
);

const isDragging = ref(false);

const syncViewportMode = (matches) => {
    if (!matches && viewMode.value === 'kanban') {
        viewMode.value = 'list';
    }
};

const onViewportChange = (event) => syncViewportMode(event.matches);

onMounted(() => {
    if (typeof window === 'undefined') {
        return;
    }

    viewportQuery = window.matchMedia('(min-width: 768px)');
    syncViewportMode(viewportQuery.matches);

    if (typeof viewportQuery.addEventListener === 'function') {
        viewportQuery.addEventListener('change', onViewportChange);
    } else if (typeof viewportQuery.addListener === 'function') {
        viewportQuery.addListener(onViewportChange);
    }
});

onUnmounted(() => {
    if (!viewportQuery) {
        return;
    }

    if (typeof viewportQuery.removeEventListener === 'function') {
        viewportQuery.removeEventListener('change', onViewportChange);
    } else if (typeof viewportQuery.removeListener === 'function') {
        viewportQuery.removeListener(onViewportChange);
    }
});

const onKanbanChange = (status, event) => {
    const moved = event?.added?.element;
    if (!moved) {
        return;
    }

    const previousStatus = moved.status;
    moved.status = status;

    router.patch(
        route('ideas.status', moved.id),
        { status },
        {
            preserveScroll: true,
            onError: () => {
                moved.status = previousStatus;
            },
        },
    );
};

const ideaCategoriesForDisplay = (idea) => {
    const multiple = idea?.categories || [];
    if (multiple.length) {
        return multiple;
    }

    return idea?.category ? [idea.category] : [];
};
</script>

<template>
    <div class="space-y-4">
        <BoPageHeader title="Ideias" subtitle="Painel de descoberta e priorização da banda" icon="mdi:lightbulb-multiple-outline">
            <template #actions>
                <div class="hidden md:block">
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
                </div>
                <Link :href="route('ideas.create')">
                    <Button class="!hidden md:!inline-flex" icon="pi pi-plus" label="Nova ideia" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-plus" rounded aria-label="Nova ideia" />
                </Link>
            </template>
        </BoPageHeader>

        <BoFilterBar :chips="filterChips" @submit="submitFilters" @reset="resetFilters" @remove-chip="removeChip" @cancel="cancelFilters">
            <div class="space-y-2">
                <label class="text-sm font-medium">Busca</label>
                <InputText v-model="localFilters.search" placeholder="Buscar por título" />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Status</label>
                <Select
                    v-model="localFilters.status"
                    :options="Object.entries(statusLabels).map(([value, label]) => ({ value, label }))"
                    option-label="label"
                    option-value="value"
                    show-clear
                    placeholder="Todos"
                />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Tipo</label>
                <Select v-model="localFilters.idea_type_id" :options="ideaTypes" option-label="name" option-value="id" show-clear />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Categoria</label>
                <Select v-model="localFilters.idea_category_id" :options="ideaCategories" option-label="name" option-value="id" show-clear />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Estilo</label>
                <Select v-model="localFilters.venue_style_id" :options="venueStyles" option-label="name" option-value="id" show-clear />
            </div>
        </BoFilterBar>

        <div v-if="viewMode === 'list'" class="hidden md:block">
            <Card>
                <template #content>
                    <DataTable :value="ideas.data" data-key="id" striped-rows>
                        <Column field="title" header="Título">
                            <template #body="{ data }">
                                <Link :href="route('ideas.show', data.id)" class="font-medium hover:underline">{{ data.title }}</Link>
                            </template>
                        </Column>
                        <Column field="type.name" header="Tipo" />
                        <Column header="Categoria" class="w-28">
                            <template #body="{ data }">
                                <div class="flex justify-center gap-1">
                                    <Tag
                                        v-for="category in ideaCategoriesForDisplay(data)"
                                        :key="category.id"
                                        severity="secondary"
                                        class="!px-1.5 !py-0.5"
                                    >
                                        <template #default>
                                            <iconify-icon
                                                :icon="category.icon || 'mdi:shape-outline'"
                                                width="14"
                                                height="14"
                                                v-tooltip.top="category.name"
                                            />
                                        </template>
                                    </Tag>
                                    <span v-if="!ideaCategoriesForDisplay(data).length" class="text-xs text-slate-400">-</span>
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
                                            <iconify-icon
                                                :icon="style.icon || 'mdi:palette-outline'"
                                                width="14"
                                                height="14"
                                                v-tooltip.top="style.name"
                                            />
                                        </template>
                                    </Tag>
                                    <span v-if="!(data.styles || []).length" class="text-xs text-slate-400">-</span>
                                </div>
                            </template>
                        </Column>
                        <Column header="Status">
                            <template #body="{ data }"><BoStatusTag :value="data.status" /></template>
                        </Column>
                        <Column header="Atualizado em">
                            <template #body="{ data }"><BoDateText :value="data.updated_at" mode="datetime" /></template>
                        </Column>
                        <Column header="Ações" class="bo-action-col w-24">
                            <template #body="{ data }">
                                <div class="flex gap-1">
                                    <Link :href="route('ideas.show', data.id)"><Button icon="pi pi-eye" size="small" outlined rounded severity="secondary" /></Link>
                                    <Link v-if="data.can_edit" :href="route('ideas.edit', data.id)"><Button icon="pi pi-pencil" size="small" outlined rounded severity="secondary" /></Link>
                                    <BoConfirmButton v-if="data.can_delete" icon="pi pi-trash" severity="danger" :rounded="true" message="Deseja remover esta ideia?" @confirm="removeIdea(data.id)" />
                                </div>
                            </template>
                        </Column>
                        <template #empty><BoDataTableEmpty /></template>
                    </DataTable>
                    <Paginator class="mt-4" :rows="ideas.per_page" :total-records="ideas.total" :first="(ideas.current_page - 1) * ideas.per_page" @page="paginate" />
                </template>
            </Card>
        </div>

        <div v-if="viewMode === 'list'" class="block space-y-3 md:hidden">
            <div v-for="idea in ideas.data" :key="idea.id" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="mb-2 flex items-start justify-between gap-2">
                    <h3 class="font-semibold">{{ idea.title }}</h3>
                    <BoStatusTag :value="idea.status" />
                </div>
                <p class="text-sm text-slate-500">{{ idea.type?.name || '-' }}</p>
                <div class="mt-2 flex flex-wrap gap-1">
                    <Tag v-for="category in ideaCategoriesForDisplay(idea)" :key="category.id" severity="secondary" class="!px-1.5 !py-0.5">
                        <template #default>
                            <iconify-icon :icon="category.icon || 'mdi:shape-outline'" width="14" height="14" v-tooltip.top="category.name" />
                        </template>
                    </Tag>
                </div>
                <div class="mt-2 flex flex-wrap gap-1">
                    <Tag v-for="style in idea.styles || []" :key="style.id" severity="secondary" class="!px-1.5 !py-0.5">
                        <template #default>
                            <iconify-icon :icon="style.icon || 'mdi:palette-outline'" width="14" height="14" v-tooltip.top="style.name" />
                        </template>
                    </Tag>
                </div>
                <p class="mt-1 text-xs text-slate-500">{{ statusLabels[idea.status] }}</p>
                <p class="text-xs text-slate-500">Atualizado em: <BoDateText :value="idea.updated_at" mode="datetime" /></p>
                <div class="mt-3 flex justify-end gap-1">
                    <Link :href="route('ideas.show', idea.id)"><Button icon="pi pi-eye" size="small" outlined rounded severity="secondary" /></Link>
                    <Link v-if="idea.can_edit" :href="route('ideas.edit', idea.id)"><Button icon="pi pi-pencil" size="small" outlined rounded severity="secondary" /></Link>
                    <BoConfirmButton v-if="idea.can_delete" icon="pi pi-trash" severity="danger" :rounded="true" message="Deseja remover esta ideia?" @confirm="removeIdea(idea.id)" />
                </div>
            </div>
        </div>

        <div v-if="viewMode === 'kanban'" class="hidden gap-4 md:grid md:grid-cols-2 xl:grid-cols-5">
            <Card v-for="column in kanbanColumns" :key="column.status">
                <template #title>{{ column.label }}</template>
                <template #content>
                    <draggable
                        :list="column.items"
                        item-key="id"
                        group="ideas"
                        class="space-y-2 rounded-lg border border-transparent p-1 transition-colors"
                        :animation="180"
                        ghost-class="bo-kanban-ghost"
                        chosen-class="bo-kanban-chosen"
                        drag-class="bo-kanban-drag"
                        @start="isDragging = true"
                        @end="isDragging = false"
                        @change="(event) => onKanbanChange(column.status, event)"
                    >
                        <template #item="{ element: idea }">
                            <Link
                                :href="route('ideas.show', idea.id)"
                                class="block rounded-lg border border-slate-200 bg-white px-3 py-2 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900"
                            >
                                <p class="truncate text-sm font-semibold">{{ idea.title }}</p>
                                <div class="mt-1 flex items-center justify-between text-xs text-slate-500">
                                    <span class="truncate">{{ idea.category?.name || 'Sem categoria' }}</span>
                                    <span class="truncate">{{ idea.type?.name || 'Sem tipo' }}</span>
                                </div>
                                <div class="mt-2 flex flex-wrap gap-1">
                                    <Tag v-for="style in idea.styles || []" :key="style.id" :value="style.name" severity="secondary" />
                                </div>
                            </Link>
                        </template>
                    </draggable>

                    <div class="mt-2">
                        <p v-if="!column.items.length" class="text-xs text-slate-500">Sem ideias nesta coluna.</p>
                    </div>
                </template>
            </Card>
        </div>
    </div>
</template>
