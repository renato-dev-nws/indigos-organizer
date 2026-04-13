<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFilterBar from '@/Components/ui/BoFilterBar.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoStatusTag from '@/Components/ui/BoStatusTag.vue';
import BoDataTableEmpty from '@/Components/ui/BoDataTableEmpty.vue';
import BoConfirmButton from '@/Components/ui/BoConfirmButton.vue';
import BoDateText from '@/Components/ui/BoDateText.vue';

defineOptions({ layout: AppLayout });
const props = defineProps({ contents: Object, filters: Object, platforms: Array, types: Array, categories: Array });

const viewMode = ref('list');

const statusLabels = {
    queued: 'Na fila',
    in_production: 'Em produção',
    cancelled: 'Cancelado',
    paused: 'Pausado',
    published: 'Publicado',
};

const localFilters = reactive({
    status: props.filters?.status ?? null,
    content_platform_id: props.filters?.content_platform_id ?? null,
    idea_type_id: props.filters?.idea_type_id ?? null,
    idea_category_id: props.filters?.idea_category_id ?? null,
    venue_style_id: props.filters?.venue_style_id ?? null,
    planned_week: props.filters?.planned_week ?? '',
    search: props.filters?.search ?? '',
});

const syncLocalFiltersFromProps = () => {
    localFilters.status = props.filters?.status ?? null;
    localFilters.content_platform_id = props.filters?.content_platform_id ?? null;
    localFilters.idea_type_id = props.filters?.idea_type_id ?? null;
    localFilters.idea_category_id = props.filters?.idea_category_id ?? null;
    localFilters.venue_style_id = props.filters?.venue_style_id ?? null;
    localFilters.planned_week = props.filters?.planned_week ?? '';
    localFilters.search = props.filters?.search ?? '';
};

const filterChips = computed(() => {
    const chips = [];
    if (localFilters.search) chips.push({ key: 'search', label: localFilters.search });
    if (localFilters.status) chips.push({ key: 'status', label: statusLabels[localFilters.status] || localFilters.status });
    if (localFilters.content_platform_id) {
        const p = props.platforms?.find((x) => x.id === localFilters.content_platform_id);
        if (p) chips.push({ key: 'content_platform_id', label: p.name });
    }
    if (localFilters.idea_type_id) {
        const t = props.types?.find((x) => x.id === localFilters.idea_type_id);
        if (t) chips.push({ key: 'idea_type_id', label: t.name });
    }
    if (localFilters.idea_category_id) {
        const c = props.categories?.find((x) => x.id === localFilters.idea_category_id);
        if (c) chips.push({ key: 'idea_category_id', label: c.name });
    }
    if (localFilters.venue_style_id) {
        const s = props.styles?.find((x) => x.id === localFilters.venue_style_id);
        if (s) chips.push({ key: 'venue_style_id', label: s.name });
    }
    if (localFilters.planned_week) chips.push({ key: 'planned_week', label: `Semana: ${localFilters.planned_week}` });
    return chips;
});

const submitFilters = () => {
    router.get(route('contents.index'), localFilters, { preserveState: true, preserveScroll: true, replace: true });
};

const resetFilters = () => {
    localFilters.status = null;
    localFilters.content_platform_id = null;
    localFilters.idea_type_id = null;
    localFilters.idea_category_id = null;
    localFilters.venue_style_id = null;
    localFilters.planned_week = '';
    localFilters.search = '';
    submitFilters();
};

const removeChip = (key) => {
    localFilters[key] = ['search', 'planned_week'].includes(key) ? '' : null;
    submitFilters();
};

const cancelFilters = () => {
    syncLocalFiltersFromProps();
};

watch(() => props.filters, syncLocalFiltersFromProps, { deep: true });

const paginate = (event) => {
    router.get(route('contents.index'), { ...localFilters, page: event.page + 1 }, { preserveState: true, preserveScroll: true, replace: true });
};

const removeContent = (id) => router.delete(route('contents.destroy', id), { preserveScroll: true });

const calendarColumns = computed(() => {
    const weekDays = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
    const grouped = weekDays.map((label) => ({ label, items: [] }));

    for (const item of props.contents.data || []) {
        if (!item.planned_publish_at) {
            continue;
        }

        const date = new Date(item.planned_publish_at);
        const day = date.getDay();
        grouped[day].items.push(item);
    }

    return grouped;
});

const fullCalendarOptions = computed(() => ({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    firstDay: 0,
    locale: 'pt-br',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek',
    },
    events: (props.contents.data || [])
        .filter((item) => item.planned_publish_at)
        .map((item) => ({
            id: item.id,
            title: item.title,
            start: item.planned_publish_at,
        })),
    eventClick: (info) => {
        info.jsEvent.preventDefault();
        router.visit(route('contents.show', info.event.id));
    },
}));
</script>

<template>
    <div class="space-y-4">
        <BoPageHeader title="Conteúdos" subtitle="Planejamento e produção editorial">
            <template #actions>
                <div class="hidden md:block">
                    <SelectButton
                        v-model="viewMode"
                        size="small"
                        :options="[
                            { label: 'Lista', value: 'list' },
                            { label: 'Programação da semana', value: 'calendar' },
                            { label: 'Calendário completo', value: 'full_calendar' },
                        ]"
                        option-label="label"
                        option-value="value"
                    />
                </div>
                <Link :href="route('contents.create')">
                    <Button class="!hidden md:!inline-flex" icon="pi pi-plus" label="Novo conteúdo" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-plus" rounded aria-label="Novo conteúdo" />
                </Link>
            </template>
        </BoPageHeader>

        <BoFilterBar :chips="filterChips" @submit="submitFilters" @reset="resetFilters" @remove-chip="removeChip" @cancel="cancelFilters">
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
                        { value: 'queued', label: 'Na fila' },
                        { value: 'in_production', label: 'Em produção' },
                        { value: 'cancelled', label: 'Cancelado' },
                        { value: 'paused', label: 'Pausado' },
                        { value: 'published', label: 'Publicado' },
                    ]"
                    option-label="label"
                    option-value="value"
                    placeholder="Todos os status"
                    show-clear
                />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Plataforma</label>
                <Select v-model="localFilters.content_platform_id" :options="platforms" option-label="name" option-value="id" placeholder="Todas as plataformas" show-clear />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Tipo</label>
                <Select v-model="localFilters.idea_type_id" :options="types" option-label="name" option-value="id" placeholder="Todos os tipos" show-clear />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Categoria</label>
                <Select v-model="localFilters.idea_category_id" :options="categories" option-label="name" option-value="id" placeholder="Todas as categorias" show-clear />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Estilo</label>
                <Select v-model="localFilters.venue_style_id" :options="styles" option-label="name" option-value="id" placeholder="Todos os estilos" show-clear />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Semana</label>
                <InputText v-model="localFilters.planned_week" placeholder="Ex: 2026-W15" />
            </div>
        </BoFilterBar>

        <div class="hidden md:block">
            <Card v-if="viewMode === 'list'">
                <template #content>
                    <DataTable :value="contents.data" data-key="id" striped-rows :sort-mode="'single'" removable-sort>
                    <Column field="title" header="Título" sortable />
                    <Column header="Plataformas">
                        <template #body="{ data }">
                            <div class="flex flex-wrap gap-1">
                                <Tag v-for="platform in data.platforms" :key="platform.id" :value="platform.name" severity="secondary" />
                            </div>
                        </template>
                    </Column>
                    <Column field="type.name" header="Tipo" sortable />
                    <Column header="Estilos">
                        <template #body="{ data }">
                            <div class="flex flex-wrap gap-1">
                                <Tag v-for="style in data.styles || []" :key="style.id" :value="style.name" severity="secondary" />
                                <span v-if="!(data.styles || []).length" class="text-xs text-slate-400">-</span>
                            </div>
                        </template>
                    </Column>
                    <Column header="Status" sort-field="status" sortable>
                        <template #body="{ data }">
                            <BoStatusTag :value="data.status" />
                        </template>
                    </Column>
                    <Column field="user.name" header="Autor" sortable />
                    <Column header="Publicação" sort-field="planned_publish_at" sortable>
                        <template #body="{ data }">
                            <BoDateText :value="data.planned_publish_at" mode="datetime" />
                        </template>
                    </Column>
                    <Column header="Ações" class="bo-action-col w-28">
                        <template #body="{ data }">
                            <div class="flex gap-1">
                                <Link :href="route('contents.show', data.id)">
                                    <Button icon="pi pi-eye" size="small" outlined rounded severity="secondary" v-tooltip.top="'Abrir'" />
                                </Link>
                                <Link :href="route('contents.edit', data.id)">
                                    <Button icon="pi pi-pencil" size="small" outlined rounded severity="secondary" v-tooltip.top="'Editar'" />
                                </Link>
                                <BoConfirmButton icon="pi pi-trash" severity="danger" message="Deseja remover este conteúdo?" :rounded="true" @confirm="removeContent(data.id)" />
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

            <div v-else-if="viewMode === 'calendar'" class="grid gap-4 lg:grid-cols-7">
                <Card v-for="column in calendarColumns" :key="column.label" class="lg:col-span-1">
                    <template #title>{{ column.label }}</template>
                    <template #content>
                        <div class="space-y-2">
                            <div v-if="!column.items.length" class="rounded border border-dashed border-slate-300 p-3 text-xs text-slate-500 dark:border-slate-700 dark:text-slate-400">
                                Sem publicações.
                            </div>
                            <div v-for="content in column.items" :key="content.id" class="rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                                <p class="mb-2 text-sm font-semibold">{{ content.title }}</p>
                                <BoStatusTag :value="content.status" />
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <Card v-else>
                <template #content>
                    <FullCalendar :options="fullCalendarOptions" />
                </template>
            </Card>
        </div>

        <div class="block space-y-3 md:hidden">
            <div v-for="content in contents.data" :key="content.id" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="mb-2 flex items-start justify-between gap-2">
                    <h3 class="font-semibold">{{ content.title }}</h3>
                    <BoStatusTag :value="content.status" />
                </div>
                <div class="mb-2 flex flex-wrap gap-1">
                    <Tag v-for="platform in content.platforms" :key="platform.id" :value="platform.name" severity="secondary" />
                </div>
                <p class="text-xs text-slate-500">{{ content.type?.name || '-' }} · {{ content.category?.name || '-' }}</p>
                <div class="mt-2 flex flex-wrap gap-1">
                    <Tag v-for="style in content.styles || []" :key="style.id" :value="style.name" severity="secondary" />
                </div>
                <p class="text-xs text-slate-500">Autor: {{ content.user?.name || '-' }}</p>
                <p class="text-xs text-slate-500">Publicação: <BoDateText :value="content.planned_publish_at" mode="datetime" /></p>
                <div class="mt-3 flex justify-end gap-1">
                    <Link :href="route('contents.show', content.id)"><Button icon="pi pi-eye" size="small" outlined rounded severity="secondary" /></Link>
                    <Link :href="route('contents.edit', content.id)"><Button icon="pi pi-pencil" size="small" outlined rounded severity="secondary" /></Link>
                    <BoConfirmButton icon="pi pi-trash" severity="danger" message="Deseja remover este conteúdo?" :rounded="true" @confirm="removeContent(content.id)" />
                </div>
            </div>
        </div>
    </div>
</template>
