<script setup>
import { watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFormSection from '@/Components/ui/BoFormSection.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';

const props = defineProps({ plan: Object });
defineOptions({ layout: AppLayout });

const form = useForm({
    title: props.plan.title,
    description: props.plan.description,
    start_date: props.plan.start_date,
    end_date: props.plan.end_date,
    status: props.plan.status,
    phases: (props.plan.phases ?? []).map((phase) => ({
        ...phase,
        completed: !!phase.completed,
        estimated_start_date: phase.estimated_start_date || '',
        estimated_end_date: phase.estimated_end_date || '',
    })),
});

const phaseDeleteWarning = 'Nao e possivel excluir fase com tarefas relacionadas! Remova o relacionamento de tarefas para excluir uma fase do planejamento.';

const addPhase = () => form.phases.push({
    title: '',
    description: '',
    completed: false,
    estimated_start_date: '',
    estimated_end_date: '',
    tasks_count: 0,
});

const movePhase = (index, direction) => {
    const target = index + direction;
    if (target < 0 || target >= form.phases.length) {
        return;
    }

    const snapshot = [...form.phases];
    [snapshot[index], snapshot[target]] = [snapshot[target], snapshot[index]];
    form.phases = snapshot;
};

const removePhase = (index) => {
    const phase = form.phases[index];
    if ((phase?.tasks_count || 0) > 0) {
        window.alert(phaseDeleteWarning);
        return;
    }

    form.phases.splice(index, 1);
};

const syncPlanDatesFromPhases = () => {
    const firstPhaseStart = form.phases.find((phase) => !!phase?.estimated_start_date)?.estimated_start_date || '';
    const lastPhaseEnd = [...form.phases].reverse().find((phase) => !!phase?.estimated_end_date)?.estimated_end_date || '';

    if (firstPhaseStart) {
        form.start_date = firstPhaseStart;
    }

    if (lastPhaseEnd) {
        form.end_date = lastPhaseEnd;
    }
};

watch(
    () => form.phases.map((phase) => `${phase.estimated_start_date || ''}|${phase.estimated_end_date || ''}`),
    () => syncPlanDatesFromPhases(),
    { deep: true },
);

const submit = () => form.put(route('plans.update', props.plan.id));
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader supratitle="PLANEJAMENTO" title="Editar planejamento" subtitle="" icon="mdi:circle-edit-outline">
            <template #actions>
                <Link :href="route('plans.show', plan.id)">
                    <Button type="button" class="!hidden md:!inline-flex" label="Visualizar" icon="pi pi-eye" outlined severity="secondary" />
                    <Button type="button" class="!inline-flex md:!hidden" icon="pi pi-eye" rounded outlined severity="secondary" aria-label="Visualizar" />
                </Link>
            </template>
        </BoPageHeader>

        <form class="space-y-4" @submit.prevent="submit">
            <BoFormSection title="Dados do plano" description="Informações principais e cronograma">
                <div class="md:col-span-2 space-y-2"><label>Título</label><InputText v-model="form.title" fluid /></div>
                <div class="md:col-span-2 space-y-2"><label>Descrição</label><Textarea v-model="form.description" rows="4" fluid /></div>
                <div class="space-y-2"><label>Início</label><DatePicker v-model="form.start_date" fluid /></div>
                <div class="space-y-2"><label>Fim</label><DatePicker v-model="form.end_date" fluid /></div>
                <div class="space-y-2">
                    <label>Status</label>
                    <Select v-model="form.status" :options="[{label:'Na fila',value:'queued'},{label:'Em execução',value:'running'},{label:'Cancelado',value:'cancelled'},{label:'Concluído',value:'completed'}]" option-label="label" option-value="value" fluid />
                </div>
            </BoFormSection>

            <Card>
                <template #title>Fases</template>
                <template #content>
                    <div class="space-y-3">
                        <div v-for="(phase, index) in form.phases" :key="phase.id || index" class="rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                            <div class="grid gap-3 md:grid-cols-12">
                                <div class="md:col-span-3 space-y-2">
                                    <label class="text-sm">Título da fase</label>
                                    <InputText v-model="phase.title" placeholder="Título da fase" fluid />
                                </div>

                                <div class="md:col-span-3 space-y-2">
                                    <label class="text-sm">Descrição</label>
                                    <InputText v-model="phase.description" placeholder="Descrição" fluid />
                                </div>

                                <div class="md:col-span-2 space-y-2">
                                    <label class="text-sm">Previsão início</label>
                                    <DatePicker v-model="phase.estimated_start_date" show-icon fluid />
                                </div>

                                <div class="md:col-span-2 space-y-2">
                                    <label class="text-sm">Previsão final</label>
                                    <DatePicker v-model="phase.estimated_end_date" show-icon fluid />
                                </div>

                                <div class="md:col-span-2 flex items-end justify-between gap-2">
                                    <div class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-700">
                                        <Checkbox v-model="phase.completed" binary :input-id="`phase-completed-${index}`" />
                                        <label :for="`phase-completed-${index}`" class="text-sm">Concluída</label>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-300 text-slate-600 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                            :disabled="index === 0"
                                            @click="movePhase(index, -1)"
                                        >
                                            <iconify-icon icon="mdi:arrow-up-circle" width="18" height="18" />
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-300 text-slate-600 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                            :disabled="index === form.phases.length - 1"
                                            @click="movePhase(index, 1)"
                                        >
                                            <iconify-icon icon="mdi:arrow-down-circle" width="18" height="18" />
                                        </button>
                                        <Button type="button" icon="pi pi-trash" text severity="danger" @click="removePhase(index)" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <Button type="button" icon="pi pi-plus" label="Adicionar fase" outlined @click="addPhase" />
                    </div>
                </template>
            </Card>

            <div class="flex justify-end gap-2">
                <Link :href="route('plans.index')"><Button type="button" label="Cancelar" outlined severity="secondary" /></Link>
                <Button type="submit" :loading="form.processing" label="Atualizar plano" icon="pi pi-save" />
            </div>
        </form>
    </div>
</template>
