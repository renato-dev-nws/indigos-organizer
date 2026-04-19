<script setup>
import { computed, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFormSection from '@/Components/ui/BoFormSection.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';

const props = defineProps({ statuses: Array, contents: Array, plans: Array, events: Array, users: Array });
defineOptions({ layout: AppLayout });

const goBack = () => {
    if (typeof window !== 'undefined' && window.history.length > 1) {
        window.history.back();
    }
};

const form = useForm({
    related_type: 'administrative',
    content_id: null,
    plan_id: null,
    plan_phase_id: null,
    event_id: null,
    title: '',
    description: '',
    assigned_user_ids: [],
    priority: 'medium',
    scheduled_for: '',
    due_date: '',
    reminder_at: '',
    task_status_id: props.statuses?.[0]?.id ?? null,
    subtasks: [],
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

watch(() => form.scheduled_for, (value) => {
    if (!value) {
        return;
    }

    const scheduledDate = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(scheduledDate.getTime())) {
        return;
    }

    form.reminder_at = new Date(scheduledDate.getTime() - (60 * 60 * 1000));
});

const submit = () => form.post(route('tasks.store'));
const addSubtask = () => form.subtasks.push({ title: '', completed: false, order: form.subtasks.length + 1 });
const removeSubtask = (index) => form.subtasks.splice(index, 1);
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Nova tarefa" supratitle="TAREFAS" subtitle="" icon="mdi:add-circle-outline">
            <template #actions>
                <div>
                    <Button class="!hidden md:!inline-flex" label="Voltar" icon="pi pi-arrow-left" outlined severity="secondary" @click="goBack" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-arrow-left" rounded outlined severity="secondary" aria-label="Voltar" @click="goBack" />
                </div>
            </template>
        </BoPageHeader>

        <form class="mx-auto max-w-[700px] space-y-4" @submit.prevent="submit">
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
                    <Message v-if="form.errors.content_id" severity="error" size="small" variant="simple">{{ form.errors.content_id }}</Message>
                </div>

                <div v-if="form.related_type === 'plan'" class="space-y-2">
                    <label>Plano</label>
                    <Select v-model="form.plan_id" :options="plans" option-label="title" option-value="id" show-clear fluid :invalid="!!form.errors.plan_id" />
                    <Message v-if="form.errors.plan_id" severity="error" size="small" variant="simple">{{ form.errors.plan_id }}</Message>
                </div>

                <div v-if="form.related_type === 'plan' && form.plan_id" class="space-y-2">
                    <label>Fase do plano (opcional)</label>
                    <Select v-model="form.plan_phase_id" :options="phaseOptions" option-label="title" option-value="id" show-clear fluid />
                </div>

                <div v-if="form.related_type === 'event'" class="space-y-2">
                    <label>Evento</label>
                    <Select v-model="form.event_id" :options="events" option-label="title" option-value="id" show-clear fluid :invalid="!!form.errors.event_id" />
                    <Message v-if="form.errors.event_id" severity="error" size="small" variant="simple">{{ form.errors.event_id }}</Message>
                </div>

                <div class="md:col-span-2 space-y-2">
                    <label>Título</label>
                    <InputText v-model="form.title" fluid :invalid="!!form.errors.title" />
                    <Message v-if="form.errors.title" severity="error" size="small" variant="simple">{{ form.errors.title }}</Message>
                </div>

                <div class="md:col-span-2 space-y-2">
                    <label>Descrição</label>
                    <Textarea v-model="form.description" rows="4" fluid />
                </div>

                <div class="space-y-2">
                    <label>Responsáveis</label>
                    <MultiSelect
                        v-model="form.assigned_user_ids"
                        :options="users"
                        option-label="name"
                        option-value="id"
                        display="chip"
                        placeholder="Todos"
                        fluid
                    />
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
                    <label>Agendado para</label>
                    <DatePicker v-model="form.scheduled_for" show-time hour-format="24" show-clear fluid />
                </div>

                <div class="space-y-2">
                    <label>Prazo</label>
                    <DatePicker v-model="form.due_date" fluid />
                </div>

                <div class="space-y-2">
                    <label>Lembrete</label>
                    <DatePicker v-model="form.reminder_at" show-time hour-format="24" fluid />
                </div>

                <div class="space-y-2">
                    <label>Status</label>
                    <Select v-model="form.task_status_id" :options="statuses" option-label="name" option-value="id" fluid />
                </div>
            </BoFormSection>

            <Card>
                <template #title>Subtarefas</template>
                <template #content>
                    <div class="space-y-2">
                        <div v-for="(subtask, index) in form.subtasks" :key="index" class="flex items-center gap-2">
                            <InputText v-model="subtask.title" class="w-full" placeholder="Título da subtarefa" />
                            <Button type="button" icon="pi pi-trash" text severity="danger" @click="removeSubtask(index)" />
                        </div>
                        <Button type="button" icon="pi pi-plus" label="Adicionar subtarefa" outlined @click="addSubtask" />
                    </div>
                </template>
            </Card>

            <div class="flex justify-end gap-2">
                <Link :href="route('tasks.index')">
                    <Button type="button" label="Cancelar" outlined severity="secondary" />
                </Link>
                <Button type="submit" :loading="form.processing" label="Salvar tarefa" icon="pi pi-save" />
            </div>
        </form>
    </div>
</template>
