<script setup>
import { computed, reactive, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
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
const page = usePage();

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

const isAdmin = computed(() => !!page.props.auth?.user?.is_admin || !!page.props.auth?.user?.is_super_admin);
const isSuperAdmin = computed(() => !!page.props.auth?.user?.is_super_admin);

const canEditUser = (user) => {
    if (user.is_super_admin && !isSuperAdmin.value) {
        return false;
    }

    return isAdmin.value || page.props.auth?.user?.id === user.id;
};

const canDeleteUser = (user) => {
    if (!isAdmin.value) {
        return false;
    }

    if (page.props.auth?.user?.id === user.id) {
        return false;
    }

    if (user.is_super_admin) {
        return false;
    }

    return true;
};
</script>

<template>
    <div class="space-y-4">
        <BoPageHeader title="Usuários" subtitle="Administração de acesso e autoria">
            <template #actions>
                <Link v-if="isAdmin" :href="route('users.create')">
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
                    <Column header="Perfil" class="w-40">
                        <template #body="{ data }">
                            <Tag
                                :value="data.is_super_admin ? 'Super Admin' : (data.is_admin ? 'Admin' : 'Usuário')"
                                :severity="data.is_super_admin ? 'success' : (data.is_admin ? 'danger' : 'secondary')"
                            />
                        </template>
                    </Column>
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
                                <Link v-if="canEditUser(data)" :href="route('users.edit', data.id)">
                                    <Button icon="pi pi-pencil" outlined rounded severity="secondary" size="small" v-tooltip.top="'Editar'" />
                                </Link>
                                <BoConfirmButton
                                    v-if="canDeleteUser(data)"
                                    icon="pi pi-trash"
                                    severity="danger"
                                    message="Deseja remover este usuário?"
                                    :rounded="true"
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
