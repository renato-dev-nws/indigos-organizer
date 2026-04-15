<script setup>
import { computed, reactive, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFilterBar from '@/Components/ui/BoFilterBar.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoConfirmButton from '@/Components/ui/BoConfirmButton.vue';
import BoDateText from '@/Components/ui/BoDateText.vue';
import BoStatusTag from '@/Components/ui/BoStatusTag.vue';

defineOptions({ layout: AppLayout });
const props = defineProps({ plans: Object, filters: Object });

const localFilters = reactive({ status: props.filters?.status ?? null, search: props.filters?.search ?? '' });
const syncLocalFiltersFromProps = () => {
    localFilters.status = props.filters?.status ?? null;
    localFilters.search = props.filters?.search ?? '';
};
const filterChips = computed(() => {
    const chips = [];
    if (localFilters.search) chips.push({ key: 'search', label: localFilters.search });
    if (localFilters.status) chips.push({ key: 'status', label: localFilters.status });
    return chips;
});

const submitFilters = () => router.get(route('plans.index'), localFilters, { preserveState: true, preserveScroll: true, replace: true });
const resetFilters = () => { localFilters.status = null; localFilters.search = ''; submitFilters(); };
const removeChip = (key) => { localFilters[key] = key === 'search' ? '' : null; submitFilters(); };
const cancelFilters = () => { syncLocalFiltersFromProps(); };
watch(() => props.filters, syncLocalFiltersFromProps, { deep: true });
const paginate = (event) => router.get(route('plans.index'), { ...localFilters, page: event.page + 1 }, { preserveState: true, preserveScroll: true, replace: true });
const removePlan = (id) => router.delete(route('plans.destroy', id), { preserveScroll: true });
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Planejamentos" subtitle="Gerencie os planos de ação da sua arte" icon="mdi:timer-music-outline">
            <template #actions>
                <Link :href="route('plans.create')">
                    <Button class="!hidden md:!inline-flex" icon="pi pi-plus" label="Novo planejamento" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-plus" rounded aria-label="Novo plano" />
                </Link>
            </template>
        </BoPageHeader>

        <BoFilterBar :chips="filterChips" @submit="submitFilters" @reset="resetFilters" @remove-chip="removeChip" @cancel="cancelFilters">
            <div class="space-y-2">
                <label class="text-sm font-medium">Busca</label>
                <InputText v-model="localFilters.search" placeholder="Título do plano" />
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium">Status</label>
                <Select
                    v-model="localFilters.status"
                    :options="[
                        { label: 'Na fila', value: 'queued' },
                        { label: 'Em execução', value: 'running' },
                        { label: 'Cancelado', value: 'cancelled' },
                        { label: 'Concluído', value: 'completed' },
                    ]"
                    option-label="label"
                    option-value="value"
                    show-clear
                />
            </div>
        </BoFilterBar>

        <div class="hidden md:block">
            <Card>
                <template #content>
                    <DataTable :value="plans.data" data-key="id" striped-rows>
                        <Column field="title" header="Título" />
                        <Column header="Status"><template #body="{ data }"><BoStatusTag :value="data.status" /></template></Column>
                        <Column header="Progresso"><template #body="{ data }"><ProgressBar :value="data.progress" style="height:0.5rem" /></template></Column>
                        <Column header="Início"><template #body="{ data }"><BoDateText :value="data.start_date" mode="date" /></template></Column>
                        <Column header="Fim"><template #body="{ data }"><BoDateText :value="data.end_date" mode="date" /></template></Column>
                        <Column header="Fases"><template #body="{ data }">{{ data.phases?.length || 0 }}</template></Column>
                        <Column header="Ações" class="bo-action-col w-28">
                            <template #body="{ data }">
                                <div class="flex gap-1">
                                    <Link :href="route('plans.show', data.id)"><Button icon="pi pi-eye" size="small" outlined rounded severity="secondary" /></Link>
                                    <Link :href="route('plans.edit', data.id)"><Button icon="pi pi-pencil" size="small" outlined rounded severity="secondary" /></Link>
                                    <BoConfirmButton icon="pi pi-trash" severity="danger" :rounded="true" message="Deseja remover este plano?" @confirm="removePlan(data.id)" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                    <Paginator class="mt-4" :rows="plans.per_page" :total-records="plans.total" :first="(plans.current_page - 1) * plans.per_page" @page="paginate" />
                </template>
            </Card>
        </div>

        <div class="block space-y-3 md:hidden">
            <div v-for="plan in plans.data" :key="plan.id" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="mb-2 flex items-start justify-between gap-2">
                    <h3 class="font-semibold">{{ plan.title }}</h3>
                    <BoStatusTag :value="plan.status" />
                </div>
                <ProgressBar :value="plan.progress" style="height:0.4rem" />
                <p class="mt-2 text-xs text-slate-500">Início: <BoDateText :value="plan.start_date" mode="date" /></p>
                <p class="text-xs text-slate-500">Fim: <BoDateText :value="plan.end_date" mode="date" /></p>
                <p class="mt-2 text-xs text-slate-500">Fases: {{ plan.phases?.length || 0 }}</p>
                <div class="mt-3 flex justify-end gap-1">
                    <Link :href="route('plans.show', plan.id)"><Button icon="pi pi-eye" size="small" outlined rounded severity="secondary" /></Link>
                    <Link :href="route('plans.edit', plan.id)"><Button icon="pi pi-pencil" size="small" outlined rounded severity="secondary" /></Link>
                    <BoConfirmButton icon="pi pi-trash" severity="danger" :rounded="true" message="Deseja remover este plano?" @confirm="removePlan(plan.id)" />
                </div>
            </div>
        </div>
    </div>
</template>
