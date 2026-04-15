<script setup>
import { computed, reactive, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFilterBar from '@/Components/ui/BoFilterBar.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoConfirmButton from '@/Components/ui/BoConfirmButton.vue';

defineOptions({ layout: AppLayout });
const props = defineProps({ sharedInfos: Object, filters: Object });

const localFilters = reactive({ search: props.filters?.search ?? '' });
const syncLocalFiltersFromProps = () => {
    localFilters.search = props.filters?.search ?? '';
};
const filterChips = computed(() => (localFilters.search ? [{ key: 'search', label: localFilters.search }] : []));

const submitFilters = () => router.get(route('shared-infos.index'), localFilters, { preserveState: true, preserveScroll: true, replace: true });
const resetFilters = () => { localFilters.search = ''; submitFilters(); };
const removeChip = () => { localFilters.search = ''; submitFilters(); };
const cancelFilters = () => { syncLocalFiltersFromProps(); };
watch(() => props.filters, syncLocalFiltersFromProps, { deep: true });
const paginate = (event) => router.get(route('shared-infos.index'), { ...localFilters, page: event.page + 1 }, { preserveState: true, preserveScroll: true, replace: true });
const removeInfo = (id) => router.delete(route('shared-infos.destroy', id), { preserveScroll: true });
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Informações" subtitle="Central de informações compartilhadas da banda" icon="ph:info-bold">
            <template #actions>
                <Link :href="route('shared-infos.create')">
                    <Button class="!hidden md:!inline-flex" icon="pi pi-plus" label="Nova informação" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-plus" rounded aria-label="Nova informação" />
                </Link>
            </template>
        </BoPageHeader>

        <BoFilterBar :chips="filterChips" @submit="submitFilters" @reset="resetFilters" @remove-chip="removeChip" @cancel="cancelFilters">
            <div class="space-y-2">
                <label class="text-sm font-medium">Busca</label>
                <InputText v-model="localFilters.search" placeholder="Título" />
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

                <div class="space-y-3 md:hidden">
                    <div v-for="item in sharedInfos.data" :key="item.id" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-start justify-between gap-2">
                            <Link :href="route('shared-infos.show', item.id)" class="font-semibold hover:underline">{{ item.title }}</Link>
                            <span class="text-xs text-slate-500">{{ item.user?.name || '-' }}</span>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-1">
                            <Tag v-for="category in item.categories || []" :key="category.id" :value="category.name" severity="secondary" />
                            <span v-if="!(item.categories || []).length" class="text-xs text-slate-400">-</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Links: {{ item.links?.length || 0 }} · Documentos: {{ item.documents?.length || 0 }}</p>
                        <div class="mt-3 flex justify-end gap-1">
                            <Link :href="route('shared-infos.show', item.id)"><Button icon="pi pi-eye" size="small" outlined rounded severity="secondary" /></Link>
                            <Link :href="route('shared-infos.edit', item.id)"><Button icon="pi pi-pencil" size="small" outlined rounded severity="secondary" /></Link>
                            <BoConfirmButton icon="pi pi-trash" severity="danger" :rounded="true" message="Deseja remover esta informação?" @confirm="removeInfo(item.id)" />
                        </div>
                    </div>
                </div>

                <Paginator class="mt-4" :rows="sharedInfos.per_page" :total-records="sharedInfos.total" :first="(sharedInfos.current_page - 1) * sharedInfos.per_page" @page="paginate" />
            </template>
        </Card>
    </div>
</template>
