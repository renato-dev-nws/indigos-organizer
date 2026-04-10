<script setup>
import { computed, reactive } from 'vue';
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

const filterChips = computed(() => {
    const chips = [];
    if (localFilters.search) chips.push({ key: 'search', label: localFilters.search });
    if (localFilters.venue_size_id) {
        const size = props.sizes?.find((s) => s.id === localFilters.venue_size_id);
        if (size) chips.push({ key: 'venue_size_id', label: size.name });
    }
    return chips;
});

const submitFilters = () => {
    router.get(route('venues.index'), localFilters, { preserveState: true, preserveScroll: true, replace: true });
};

const resetFilters = () => {
    localFilters.venue_size_id = null;
    localFilters.search = '';
    submitFilters();
};

const removeChip = (key) => {
    localFilters[key] = key === 'search' ? '' : null;
    submitFilters();
};

const paginate = (event) => {
    router.get(route('venues.index'), { ...localFilters, page: event.page + 1 }, { preserveState: true, preserveScroll: true, replace: true });
};

const removeVenue = (id) => router.delete(route('venues.destroy', id), { preserveScroll: true });
</script>

<template>
    <div class="space-y-4">
        <BoPageHeader title="Casas de Show" subtitle="Gestão de venues e contatos">
            <template #actions>
                <Link :href="route('venues.create')">
                    <Button icon="pi pi-plus" label="Nova casa" />
                </Link>
            </template>
        </BoPageHeader>

        <BoFilterBar :chips="filterChips" @submit="submitFilters" @reset="resetFilters" @remove-chip="removeChip">
            <div class="space-y-2">
                <label class="text-sm font-medium">Busca</label>
                <IconField>
                    <InputIcon class="pi pi-search" />
                    <InputText v-model="localFilters.search" placeholder="Buscar por nome" />
                </IconField>
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Porte</label>
                <Select v-model="localFilters.venue_size_id" :options="sizes" option-label="name" option-value="id" placeholder="Todos os portes" show-clear />
            </div>
        </BoFilterBar>

        <Card>
            <template #content>
                <DataTable :value="venues.data" data-key="id" striped-rows :sort-mode="'single'" removable-sort>
                    <Column field="name" header="Nome" sortable />
                    <Column field="size.name" header="Porte" sortable />
                    <Column field="contact_name" header="Contato" sortable />
                    <Column field="phone" header="Telefone" />
                    <Column field="email" header="E-mail" />
                    <Column header="Ações" class="bo-action-col w-24">
                        <template #body="{ data }">
                            <div class="flex gap-1">
                                <Link :href="route('venues.show', data.id)">
                                    <Button icon="pi pi-eye" size="small" outlined rounded severity="secondary" v-tooltip.top="'Abrir'" />
                                </Link>
                                <Link :href="route('venues.edit', data.id)">
                                    <Button icon="pi pi-pencil" size="small" outlined rounded severity="secondary" v-tooltip.top="'Editar'" />
                                </Link>
                                <BoConfirmButton icon="pi pi-trash" severity="danger" message="Deseja remover esta casa de show?" :rounded="true" @confirm="removeVenue(data.id)" />
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

