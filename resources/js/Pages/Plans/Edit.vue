<script setup>
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
    completion_progress: props.plan.progress,
    status: props.plan.status,
    phases: props.plan.phases ?? [],
});

const addPhase = () => form.phases.push({ title: '', description: '', order: form.phases.length + 1 });
const removePhase = (index) => form.phases.splice(index, 1);
const submit = () => {
    form
        .transform((data) => ({
            ...data,
            progress: data.completion_progress,
        }))
        .put(route('plans.update', props.plan.id));
};
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader supratitle="PLANEJAMENTO" title="Editar planejamento" subtitle="" icon="mdi:circle-edit-outline">
            <template #actions>
                <Link :href="route('plans.show', plan.id)">
                    <Button class="!hidden md:!inline-flex" label="Visualizar" icon="pi pi-eye" outlined severity="secondary" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-eye" rounded outlined severity="secondary" aria-label="Visualizar" />
                </Link>
            </template>
        </BoPageHeader>

        <form class="space-y-4" @submit.prevent="submit">
            <BoFormSection title="Dados do plano" description="Informações principais e cronograma">
                <div class="md:col-span-2 space-y-2"><label>Título</label><InputText v-model="form.title" fluid /></div>
                <div class="md:col-span-2 space-y-2"><label>Descrição</label><Textarea v-model="form.description" rows="4" fluid /></div>
                <div class="space-y-2"><label>Início</label><DatePicker v-model="form.start_date" fluid /></div>
                <div class="space-y-2"><label>Fim</label><DatePicker v-model="form.end_date" fluid /></div>
                <div class="space-y-2"><label>Progresso</label><InputNumber v-model="form.completion_progress" :min="0" :max="100" fluid /></div>
                <div class="space-y-2">
                    <label>Status</label>
                    <Select v-model="form.status" :options="[{label:'Na fila',value:'queued'},{label:'Em execução',value:'running'},{label:'Cancelado',value:'cancelled'},{label:'Concluído',value:'completed'}]" option-label="label" option-value="value" fluid />
                </div>
            </BoFormSection>

            <Card>
                <template #title>Fases</template>
                <template #content>
                    <div class="space-y-3">
                        <div v-for="(phase, index) in form.phases" :key="phase.id || index" class="grid gap-3 md:grid-cols-4">
                            <InputText v-model="phase.title" placeholder="Título da fase" />
                            <InputText v-model="phase.description" placeholder="Descrição" class="md:col-span-2" />
                            <div class="flex items-center gap-2">
                                <InputNumber v-model="phase.order" :min="1" />
                                <Button type="button" icon="pi pi-trash" text severity="danger" @click="removePhase(index)" />
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
