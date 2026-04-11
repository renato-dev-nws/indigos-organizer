<script setup>
import { computed, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFilterBar from '@/Components/ui/BoFilterBar.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoConfirmButton from '@/Components/ui/BoConfirmButton.vue';

defineOptions({ layout: AppLayout });
const props = defineProps({ sharedInfos: Object, filters: Object });

const localFilters = reactive({ search: props.filters?.search ?? '' });
const filterChips = computed(() => (localFilters.search ? [{ key: 'search', label: localFilters.search }] : []));

const submitFilters = () => router.get(route('shared-infos.index'), localFilters, { preserveState: true, preserveScroll: true, replace: true });
const resetFilters = () => { localFilters.search = ''; submitFilters(); };
const removeChip = () => { localFilters.search = ''; submitFilters(); };
const paginate = (event) => router.get(route('shared-infos.index'), { ...localFilters, page: event.page + 1 }, { preserveState: true, preserveScroll: true, replace: true });
const removeInfo = (id) => router.delete(route('shared-infos.destroy', id), { preserveScroll: true });
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Informações" subtitle="Central de informações compartilhadas da banda">
            <template #actions>
                <Link :href="route('shared-infos.create')"><Button icon="pi pi-plus" label="Nova informação" /></Link>
            </template>
        </BoPageHeader>

        <BoFilterBar :chips="filterChips" @submit="submitFilters" @reset="resetFilters" @remove-chip="removeChip">
            <div class="space-y-2">
                <label class="text-sm font-medium">Busca</label>
                <InputText v-model="localFilters.search" placeholder="Título" />
            </div>
        </BoFilterBar>

        <Card>
            <template #content>
                <DataTable :value="sharedInfos.data" data-key="id" striped-rows>
                    <Column field="title" header="Título" />
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
                <Paginator class="mt-4" :rows="sharedInfos.per_page" :total-records="sharedInfos.total" :first="(sharedInfos.current_page - 1) * sharedInfos.per_page" @page="paginate" />
            </template>
        </Card>
    </div>
</template>
