<script setup>
import { computed, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFormSection from '@/Components/ui/BoFormSection.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';

const props = defineProps({ task: Object, statuses: Array, contents: Array, plans: Array, events: Array, users: Array });
defineOptions({ layout: AppLayout });

const form = useForm({
    related_type: props.task.related_type,
    content_id: props.task.content_id,
    plan_id: props.task.plan_id,
    plan_phase_id: props.task.plan_phase_id,
    event_id: props.task.event_id,
    title: props.task.title,
    description: props.task.description,
    assigned_user_id: props.task.assigned_user_id,
    priority: props.task.priority,
    due_date: props.task.due_date,
    task_status_id: props.task.task_status_id,
});

const selectedPlan = computed(() => props.plans?.find((plan) => plan.id === form.plan_id));
const phaseOptions = computed(() => selectedPlan.value?.phases ?? []);

watch(() => form.related_type, () => {
    form.content_id = null;
    form.plan_id = null;
    form.plan_phase_id = null;
    form.event_id = null;
});

watch(() => form.plan_id, () => {
    form.plan_phase_id = null;
});

const submit = () => form.put(route('tasks.update', props.task.id));
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Editar tarefa" subtitle="Atualize o vínculo e os metadados da tarefa">
            <template #actions>
                <Link :href="route('tasks.index')">
                    <Button class="!hidden md:!inline-flex" label="Voltar" icon="pi pi-arrow-left" outlined severity="secondary" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-arrow-left" rounded outlined severity="secondary" aria-label="Voltar" />
                </Link>
            </template>
        </BoPageHeader>

        <form class="space-y-4" @submit.prevent="submit">
            <BoFormSection title="Dados da tarefa" description="Defina vínculo, responsável e prioridade">
                <div class="space-y-2">
                    <label>Relacionada a</label>
                    <Select
                        v-model="form.related_type"
                        :options="[
                            { label: 'Conteúdo', value: 'content' },
                            { label: 'Plano', value: 'plan' },
                            { label: 'Evento', value: 'event' },
                            { label: 'Administrativo', value: 'administrative' },
                        ]"
                        option-label="label"
                        option-value="value"
                        fluid
                    />
                </div>

                <div v-if="form.related_type === 'content'" class="space-y-2">
                    <label>Conteúdo</label>
                    <Select v-model="form.content_id" :options="contents" option-label="title" option-value="id" show-clear fluid :invalid="!!form.errors.content_id" />
                </div>

                <div v-if="form.related_type === 'plan'" class="space-y-2">
                    <label>Plano</label>
                    <Select v-model="form.plan_id" :options="plans" option-label="title" option-value="id" show-clear fluid :invalid="!!form.errors.plan_id" />
                </div>

                <div v-if="form.related_type === 'plan' && form.plan_id" class="space-y-2">
                    <label>Fase do plano (opcional)</label>
                    <Select v-model="form.plan_phase_id" :options="phaseOptions" option-label="title" option-value="id" show-clear fluid />
                </div>

                <div v-if="form.related_type === 'event'" class="space-y-2">
                    <label>Evento</label>
                    <Select v-model="form.event_id" :options="events" option-label="title" option-value="id" show-clear fluid :invalid="!!form.errors.event_id" />
                </div>

                <div class="md:col-span-2 space-y-2">
                    <label>Título</label>
                    <InputText v-model="form.title" fluid :invalid="!!form.errors.title" />
                </div>

                <div class="md:col-span-2 space-y-2">
                    <label>Descrição</label>
                    <Textarea v-model="form.description" rows="4" fluid />
                </div>

                <div class="space-y-2">
                    <label>Responsável</label>
                    <Select v-model="form.assigned_user_id" :options="[{ id: null, name: 'Todos' }, ...users]" option-label="name" option-value="id" show-clear fluid />
                </div>

                <div class="space-y-2">
                    <label>Prioridade</label>
                    <Select
                        v-model="form.priority"
                        :options="[
                            { label: 'Baixa', value: 'low' },
                            { label: 'Média', value: 'medium' },
                            { label: 'Alta', value: 'high' },
                            { label: 'Urgente', value: 'urgent' },
                        ]"
                        option-label="label"
                        option-value="value"
                        fluid
                    />
                </div>

                <div class="space-y-2">
                    <label>Prazo</label>
                    <DatePicker v-model="form.due_date" fluid />
                </div>

                <div class="space-y-2">
                    <label>Status</label>
                    <Select v-model="form.task_status_id" :options="statuses" option-label="name" option-value="id" fluid />
                </div>
            </BoFormSection>

            <div class="flex justify-end gap-2">
                <Link :href="route('tasks.index')">
                    <Button type="button" label="Cancelar" outlined severity="secondary" />
                </Link>
                <Button type="submit" :loading="form.processing" label="Atualizar tarefa" icon="pi pi-save" />
            </div>
        </form>
    </div>
</template>
