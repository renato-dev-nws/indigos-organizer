<script setup>
import { reactive } from 'vue';
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

const submitFilters = () => {
    router.get(route('users.index'), localFilters, { preserveState: true, preserveScroll: true, replace: true });
};

const resetFilters = () => {
    localFilters.search = '';
    submitFilters();
};

const paginate = (event) => {
    router.get(route('users.index'), { ...localFilters, page: event.page + 1 }, { preserveState: true, preserveScroll: true, replace: true });
};

const removeUser = (id) => {
    router.delete(route('users.destroy', id), { preserveScroll: true });
};
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Usuários" subtitle="Administração de acesso e autoria" >
            <template #actions>
                <Link :href="route('users.create')">
                    <Button icon="pi pi-plus" label="Novo usuário" />
                </Link>
            </template>
        </BoPageHeader>

        <BoFilterBar @submit="submitFilters" @reset="resetFilters">
            <IconField>
                <InputIcon class="pi pi-search" />
                <InputText v-model="localFilters.search" placeholder="Buscar por nome ou e-mail" />
            </IconField>
        </BoFilterBar>

        <Card>
            <template #content>
                <DataTable :value="users.data" data-key="id" striped-rows responsive-layout="scroll">
                    <Column field="name" header="Nome" />
                    <Column field="email" header="E-mail" />
                    <Column field="theme" header="Tema" />
                    <Column header="Autoria">
                        <template #body="{ data }">
                            <div class="text-xs text-slate-500 dark:text-slate-400">
                                Ideias: {{ data.ideas_count }} | Conteúdos: {{ data.contents_count }} | Tarefas: {{ data.tasks_count }}
                            </div>
                        </template>
                    </Column>
                    <Column header="Criado em">
                        <template #body="{ data }">
                            <BoDateText :value="data.created_at" mode="datetime" />
                        </template>
                    </Column>
                    <Column header="Ações" class="min-w-52">
                        <template #body="{ data }">
                            <div class="flex flex-wrap gap-2">
                                <Link :href="route('users.edit', data.id)">
                                    <Button icon="pi pi-pencil" label="Editar" outlined severity="secondary" size="small" />
                                </Link>
                                <BoConfirmButton
                                    label="Excluir"
                                    icon="pi pi-trash"
                                    severity="danger"
                                    message="Deseja remover este usuário?"
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
