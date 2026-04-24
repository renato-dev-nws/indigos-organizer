<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFilterBar from '@/Components/ui/BoFilterBar.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoConfirmButton from '@/Components/ui/BoConfirmButton.vue';

defineOptions({ layout: AppLayout });
const props = defineProps({ sharedInfos: Object, categories: Array, filters: Object });

const localFilters = reactive({
    title: props.filters?.title ?? '',
    shared_info_category_id: props.filters?.shared_info_category_id ?? null,
    search: props.filters?.search ?? '',
});
const quickSearch = ref(props.filters?.search ?? '');
const quickSearchTimer = ref(null);
const syncLocalFiltersFromProps = () => {
    localFilters.title = props.filters?.title ?? '';
    localFilters.shared_info_category_id = props.filters?.shared_info_category_id ?? null;
    localFilters.search = props.filters?.search ?? '';
    quickSearch.value = props.filters?.search ?? '';
};

const filterChips = computed(() => {
    const chips = [];
    if (localFilters.title) {
        chips.push({ key: 'title', label: `Título: ${localFilters.title}` });
    }
    if (localFilters.shared_info_category_id) {
        const category = (props.categories || []).find((item) => item.id === localFilters.shared_info_category_id);
        if (category) {
            chips.push({ key: 'shared_info_category_id', label: category.name });
        }
    }

    return chips;
});

const submitFilters = () => router.get(route('shared-infos.index'), localFilters, { preserveState: true, preserveScroll: true, replace: true });
const resetFilters = () => {
    localFilters.title = '';
    localFilters.shared_info_category_id = null;
    localFilters.search = '';
    quickSearch.value = '';
    submitFilters();
};
const removeChip = (key) => {
    localFilters[key] = ['title', 'search'].includes(key) ? '' : null;
    if (key === 'search') {
        quickSearch.value = '';
    }
    submitFilters();
};
const cancelFilters = () => { syncLocalFiltersFromProps(); };
watch(() => props.filters, syncLocalFiltersFromProps, { deep: true });
watch(quickSearch, (value) => {
    if (quickSearchTimer.value) {
        clearTimeout(quickSearchTimer.value);
    }

    quickSearchTimer.value = setTimeout(() => {
        if (localFilters.search === value) {
            return;
        }

        localFilters.search = value;
        submitFilters();
    }, 1000);
});
const paginate = (event) => router.get(route('shared-infos.index'), { ...localFilters, page: event.page + 1 }, { preserveState: true, preserveScroll: true, replace: true });
const removeInfo = (id) => router.delete(route('shared-infos.destroy', id), { preserveScroll: true });
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Informações" subtitle="Central de informações compartilhadas da banda" helper="informacoes-uteis" icon="ph:info-bold">
            <template #actions>
                <Link :href="route('shared-infos.create')">
                    <Button class="!hidden md:!inline-flex" icon="pi pi-plus" label="Nova informação" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-plus" rounded aria-label="Nova informação" />
                </Link>
            </template>
        </BoPageHeader>

        <BoFilterBar :chips="filterChips" @submit="submitFilters" @reset="resetFilters" @remove-chip="removeChip" @cancel="cancelFilters">
            <template #right-actions>
                <div class="relative">
                    <InputText v-model="quickSearch" class="w-72 pr-8" placeholder="Busca rápida de informações" />
                    <button
                        v-if="quickSearch"
                        type="button"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                        aria-label="Limpar busca"
                        @click="quickSearch = ''"
                    >
                        <i class="pi pi-times-circle" />
                    </button>
                </div>
            </template>

            <div class="space-y-2">
                <label class="text-sm font-medium">Título</label>
                <InputText v-model="localFilters.title" placeholder="Filtrar por título" />
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium">Categoria</label>
                <Select
                    v-model="localFilters.shared_info_category_id"
                    :options="categories || []"
                    option-label="name"
                    option-value="id"
                    show-clear
                    placeholder="Todas"
                />
            </div>
        </BoFilterBar>

        <Card>
            <template #content>
                <div class="hidden md:block">
                    <DataTable :value="sharedInfos.data" data-key="id" striped-rows>
                    <Column field="title" header="Título">
                        <template #body="{ data }">
                            <Link :href="route('shared-infos.show', data.id)" class="font-medium hover:underline">{{ data.title }}</Link>
                        </template>
                    </Column>
                    <Column header="Categorias">
                        <template #body="{ data }">
                            <div class="flex flex-wrap gap-1">
                                <Tag v-for="category in data.categories || []" :key="category.id" :value="category.name" severity="secondary" />
                                <span v-if="!(data.categories || []).length" class="text-xs text-slate-400">-</span>
                            </div>
                        </template>
                    </Column>
                    <Column field="user.name" header="Autor" />
                    <Column header="Links"><template #body="{ data }">{{ data.links?.length || 0 }}</template></Column>
                    <Column header="Documentos"><template #body="{ data }">{{ data.documents?.length || 0 }}</template></Column>
                    <Column header="Ações" class="bo-action-col w-28">
                        <template #body="{ data }">
                            <div class="flex gap-1">
                                <Link :href="route('shared-infos.show', data.id)"><Button icon="pi pi-eye" size="small" outlined rounded severity="secondary" /></Link>
                                <Link :href="route('shared-infos.edit', data.id)"><Button icon="pi pi-pencil" size="small" outlined rounded severity="secondary" /></Link>
                                <BoConfirmButton icon="pi pi-trash" severity="danger" :rounded="true" message="Deseja remover esta informação?" @confirm="removeInfo(data.id)" />
                            </div>
                        </template>
                    </Column>
                    </DataTable>
                </div>

                <div v-if="sharedInfos.data.length" class="space-y-3 md:hidden">
                    <div v-for="item in sharedInfos.data" :key="item.id" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="grid grid-cols-5 gap-3">
                            <div class="col-span-3 space-y-2">
                                <Link :href="route('shared-infos.show', item.id)" class="block text-base font-semibold leading-5 hover:underline">{{ item.title }}</Link>

                                <div>
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Categorias</p>
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        <Tag v-for="category in item.categories || []" :key="category.id" :value="category.name" severity="secondary" />
                                        <span v-if="!(item.categories || []).length" class="text-xs text-slate-400">-</span>
                                    </div>
                                </div>

                                <div>
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Autor</p>
                                    <p class="text-sm text-slate-700 dark:text-slate-200">{{ item.user?.name || '-' }}</p>
                                </div>

                                <div>
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Referências</p>
                                    <p class="text-xs text-slate-600 dark:text-slate-300">Links: {{ item.links?.length || 0 }} · Documentos: {{ item.documents?.length || 0 }}</p>
                                </div>
                            </div>

                            <div class="col-span-2 flex flex-col items-end justify-between gap-3">
                                <Tag severity="secondary" class="!px-1.5 !py-0.5">{{ item.categories?.length || 0 }} cat.</Tag>

                                <div class="flex flex-wrap justify-end gap-1">
                                    <Link :href="route('shared-infos.edit', item.id)"><Button icon="pi pi-pencil" size="small" outlined rounded severity="secondary" /></Link>
                                    <BoConfirmButton icon="pi pi-trash" severity="danger" message="Deseja remover esta informação?" :rounded="true" @confirm="removeInfo(item.id)" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="md:hidden">
                    <div class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center dark:border-slate-700 dark:bg-slate-900">
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Nenhuma informação ainda.</p>
                        <Link :href="route('shared-infos.create')" class="mt-3 inline-flex">
                            <Button label="Crie sua primeira informação" icon="pi pi-plus" size="small" />
                        </Link>
                    </div>
                </div>

                <Paginator class="mt-4" :rows="sharedInfos.per_page" :total-records="sharedInfos.total" :first="(sharedInfos.current_page - 1) * sharedInfos.per_page" @page="paginate" />
            </template>
        </Card>
    </div>
</template>
