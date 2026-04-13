<script setup>
import { computed, reactive, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFilterBar from '@/Components/ui/BoFilterBar.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoConfirmButton from '@/Components/ui/BoConfirmButton.vue';
import BoDataTableEmpty from '@/Components/ui/BoDataTableEmpty.vue';
import BoDateText from '@/Components/ui/BoDateText.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    users: Object,
    filters: Object,
});

const localFilters = reactive({
    search: props.filters?.search ?? '',
});

const syncLocalFiltersFromProps = () => {
    localFilters.search = props.filters?.search ?? '';
};

const filterChips = computed(() => {
    const chips = [];
    if (localFilters.search) chips.push({ key: 'search', label: localFilters.search });
    return chips;
});

const submitFilters = () => {
    router.get(route('users.index'), localFilters, { preserveState: true, preserveScroll: true, replace: true });
};

const resetFilters = () => {
    localFilters.search = '';
    submitFilters();
};

const removeChip = (key) => {
    localFilters[key] = '';
    submitFilters();
};

const cancelFilters = () => {
    syncLocalFiltersFromProps();
};

watch(() => props.filters, syncLocalFiltersFromProps, { deep: true });

const paginate = (event) => {
    router.get(route('users.index'), { ...localFilters, page: event.page + 1 }, { preserveState: true, preserveScroll: true, replace: true });
};

const removeUser = (id) => {
    router.delete(route('users.destroy', id), { preserveScroll: true });
};
</script>

<template>
    <div class="space-y-4">
        <BoPageHeader title="Usuários" subtitle="Administração de acesso e autoria">
            <template #actions>
                <Link :href="route('users.create')">
                    <Button class="!hidden md:!inline-flex" icon="pi pi-plus" label="Novo usuário" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-plus" rounded aria-label="Novo usuário" />
                </Link>
            </template>
        </BoPageHeader>

        <BoFilterBar :chips="filterChips" @submit="submitFilters" @reset="resetFilters" @remove-chip="removeChip" @cancel="cancelFilters">
            <div class="space-y-2">
                <label class="text-sm font-medium">Busca</label>
                <IconField>
                    <InputIcon class="pi pi-search" />
                    <InputText v-model="localFilters.search" placeholder="Buscar por nome ou e-mail" />
                </IconField>
            </div>
        </BoFilterBar>

        <Card>
            <template #content>
                <DataTable :value="users.data" data-key="id" striped-rows :sort-mode="'single'" removable-sort>
                    <Column field="name" header="Nome" sortable />
                    <Column field="email" header="E-mail" sortable />
                    <Column field="theme" header="Tema" />
                    <Column header="Autoria" class="w-56">
                        <template #body="{ data }">
                            <div class="flex gap-3 text-xs text-muted-color">
                                <span><i class="pi pi-lightbulb mr-1" />{{ data.ideas_count }}</span>
                                <span><i class="pi pi-video mr-1" />{{ data.contents_count }}</span>
                                <span><i class="pi pi-check-square mr-1" />{{ data.tasks_count }}</span>
                            </div>
                        </template>
                    </Column>
                    <Column header="Criado em" sort-field="created_at" sortable>
                        <template #body="{ data }">
                            <BoDateText :value="data.created_at" mode="date" />
                        </template>
                    </Column>
                    <Column header="Ações" class="bo-action-col w-20">
                        <template #body="{ data }">
                            <div class="flex gap-1">
                                <Link :href="route('users.edit', data.id)">
                                    <Button icon="pi pi-pencil" outlined rounded severity="secondary" size="small" v-tooltip.top="'Editar'" />
                                </Link>
                                <BoConfirmButton
                                    icon="pi pi-trash"
                                    severity="danger"
                                    message="Deseja remover este usuário?"
                                    :rounded="true"
                                    :disabled="$page.props.auth.user?.id === data.id"
                                    @confirm="removeUser(data.id)"
                                />
                            </div>
                        </template>
                    </Column>

                    <template #empty>
                        <BoDataTableEmpty />
                    </template>
                </DataTable>

                <Paginator
                    class="mt-4"
                    :rows="users.per_page"
                    :total-records="users.total"
                    :first="(users.current_page - 1) * users.per_page"
                    @page="paginate"
                />
            </template>
        </Card>
    </div>
</template>
