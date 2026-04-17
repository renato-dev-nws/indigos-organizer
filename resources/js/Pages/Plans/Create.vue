<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFormSection from '@/Components/ui/BoFormSection.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';

defineOptions({ layout: AppLayout });

const goBack = () => {
    if (typeof window !== 'undefined' && window.history.length > 1) {
        window.history.back();
    }
};

const form = useForm({
    title: '',
    description: '',
    start_date: '',
    end_date: '',
    status: 'queued',
    phases: [],
});

const addPhase = () => form.phases.push({ title: '', description: '', order: form.phases.length + 1, completed: false });
const removePhase = (index) => form.phases.splice(index, 1);
const submit = () => form.post(route('plans.store'));
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Novo planejamento" supratitle="PLANEJAMENTOS" icon="mdi:add-circle-outline">
            <template #actions>
                <div>
                    <Button class="!hidden md:!inline-flex" label="Voltar" icon="pi pi-arrow-left" outlined severity="secondary" @click="goBack" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-arrow-left" rounded outlined severity="secondary" aria-label="Voltar" @click="goBack" />
                </div>
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
                        <div v-for="(phase, index) in form.phases" :key="index" class="grid gap-3 md:grid-cols-5">
                            <InputText v-model="phase.title" placeholder="Título da fase" />
                            <InputText v-model="phase.description" placeholder="Descrição" class="md:col-span-2" />
                            <div class="flex items-center gap-2">
                                <InputNumber v-model="phase.order" :min="1" />
                            </div>
                            <div class="flex items-center gap-2 justify-end">
                                <div class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-700">
                                    <Checkbox v-model="phase.completed" binary :input-id="`phase-completed-${index}`" />
                                    <label :for="`phase-completed-${index}`" class="text-sm">Concluída</label>
                                </div>
                                <Button type="button" icon="pi pi-trash" text severity="danger" @click="removePhase(index)" />
                            </div>
                        </div>
                        <Button type="button" icon="pi pi-plus" label="Adicionar fase" outlined @click="addPhase" />
                    </div>
                </template>
            </Card>

            <div class="flex justify-end gap-2">
                <Link :href="route('plans.index')"><Button type="button" label="Cancelar" outlined severity="secondary" /></Link>
                <Button type="submit" :loading="form.processing" label="Salvar plano" icon="pi pi-save" />
            </div>
        </form>
    </div>
</template>
