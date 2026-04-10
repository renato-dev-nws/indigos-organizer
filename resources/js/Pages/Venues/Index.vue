<script setup>
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFilterBar from '@/Components/ui/BoFilterBar.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoDataTableEmpty from '@/Components/ui/BoDataTableEmpty.vue';
import BoConfirmButton from '@/Components/ui/BoConfirmButton.vue';

defineOptions({ layout: AppLayout });
const props = defineProps({ venues: Object, sizes: Array, filters: Object });

const localFilters = reactive({
    venue_size_id: props.filters?.venue_size_id ?? null,
    search: props.filters?.search ?? '',
});

const submitFilters = () => {
    router.get(route('venues.index'), localFilters, { preserveState: true, preserveScroll: true, replace: true });
};

const resetFilters = () => {
    localFilters.venue_size_id = null;
    localFilters.search = '';
    submitFilters();
};

const paginate = (event) => {
    router.get(route('venues.index'), { ...localFilters, page: event.page + 1 }, { preserveState: true, preserveScroll: true, replace: true });
};

const removeVenue = (id) => router.delete(route('venues.destroy', id), { preserveScroll: true });
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Casas de Show" subtitle="Gestao de venues e contatos">
            <template #actions>
                <Link :href="route('venues.create')">
                    <Button icon="pi pi-plus" label="Nova casa" />
                </Link>
            </template>
        </BoPageHeader>

        <BoFilterBar @submit="submitFilters" @reset="resetFilters">
            <IconField>
                <InputIcon class="pi pi-search" />
                <InputText v-model="localFilters.search" placeholder="Buscar por nome" />
            </IconField>
            <Select v-model="localFilters.venue_size_id" :options="sizes" option-label="name" option-value="id" placeholder="Porte" show-clear />
        </BoFilterBar>

        <Card>
            <template #content>
                <DataTable :value="venues.data" data-key="id" striped-rows responsive-layout="scroll">
                    <Column field="name" header="Nome" />
                    <Column header="Porte">
                        <template #body="{ data }">{{ data.size?.name || '-' }}</template>
                    </Column>
                    <Column field="contact_name" header="Contato" />
                    <Column field="phone" header="Telefone" />
                    <Column field="email" header="Email" />
                    <Column header="Acoes" class="min-w-56">
                        <template #body="{ data }">
                            <div class="flex flex-wrap gap-2">
                                <Link :href="route('venues.show', data.id)">
                                    <Button icon="pi pi-eye" label="Abrir" size="small" outlined severity="secondary" />
                                </Link>
                                <Link :href="route('venues.edit', data.id)">
                                    <Button icon="pi pi-pencil" label="Editar" size="small" outlined severity="secondary" />
                                </Link>
                                <BoConfirmButton label="Excluir" icon="pi pi-trash" severity="danger" message="Deseja remover esta casa de show?" @confirm="removeVenue(data.id)" />
                            </div>
                        </template>
                    </Column>
                    <template #empty>
                        <BoDataTableEmpty />
                    </template>
                </DataTable>

                <Paginator
                    class="mt-4"
                    :rows="venues.per_page"
                    :total-records="venues.total"
                    :first="(venues.current_page - 1) * venues.per_page"
                    @page="paginate"
                />
            </template>
        </Card>
    </div>
 </template>
