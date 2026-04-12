<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppSpeechTextareaAssist from '@/Components/AppSpeechTextareaAssist.vue';

const props = defineProps({
    visible: { type: Boolean, default: false },
    task: { type: Object, default: null },
    statuses: { type: Array, default: () => [] },
    contents: { type: Array, default: () => [] },
    plans: { type: Array, default: () => [] },
    users: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:visible', 'saved']);

const form = useForm({
    related_type: 'administrative',
    content_id: null,
    plan_id: null,
    plan_phase_id: null,
    title: '',
    description: '',
    assigned_user_id: null,
    priority: 'medium',
    due_date: '',
    reminder_at: '',
    task_status_id: null,
    subtasks: [],
});

const selectedPlan = computed(() => props.plans?.find((plan) => plan.id === form.plan_id));
const phaseOptions = computed(() => selectedPlan.value?.phases ?? []);

const closeModal = () => emit('update:visible', false);

const hydrateForm = () => {
    if (!props.task) {
        form.defaults({
            related_type: 'administrative',
            content_id: null,
            plan_id: null,
            plan_phase_id: null,
            title: '',
            description: '',
            assigned_user_id: null,
            priority: 'medium',
            due_date: '',
            reminder_at: '',
            task_status_id: props.statuses?.[0]?.id ?? null,
            subtasks: [],
        });
        form.reset();
        return;
    }

    form.defaults({
        related_type: props.task.related_type,
        content_id: props.task.content_id,
        plan_id: props.task.plan_id,
        plan_phase_id: props.task.plan_phase_id,
        title: props.task.title,
        description: props.task.description,
        assigned_user_id: props.task.assigned_user_id,
        priority: props.task.priority,
        due_date: props.task.due_date,
        reminder_at: props.task.reminder_at,
        task_status_id: props.task.task_status_id,
        subtasks: props.task.subtasks || [],
    });
    form.reset();
};

watch(() => props.visible, (isVisible) => {
    if (isVisible) {
        hydrateForm();
    }
});

watch(() => form.related_type, () => {
    form.content_id = null;
    form.plan_id = null;
    form.plan_phase_id = null;
});

watch(() => form.plan_id, () => {
    form.plan_phase_id = null;
});

const addSubtask = () => form.subtasks.push({ title: '', completed: false, order: form.subtasks.length + 1 });
const removeSubtask = (index) => form.subtasks.splice(index, 1);

const submit = () => {
    if (props.task?.id) {
        form.put(route('tasks.update', props.task.id), {
            preserveScroll: true,
            onSuccess: () => {
                emit('saved');
                closeModal();
            },
        });
        return;
    }

    form.post(route('tasks.store'), {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            closeModal();
        },
    });
};
</script>

<template>
    <Dialog :visible="visible" modal :header="task?.id ? 'Editar tarefa' : 'Nova tarefa'" :style="{ width: '56rem', maxWidth: '96vw' }" @update:visible="emit('update:visible', $event)">
        <form class="space-y-4" @submit.prevent="submit">
            <div class="grid gap-3 md:grid-cols-2">
                <div class="space-y-2">
                    <label>Relacionada a</label>
                    <Select
                        v-model="form.related_type"
                        :options="[
                            { label: 'Conteúdo', value: 'content' },
                            { label: 'Plano', value: 'plan' },
                            { label: 'Administrativo', value: 'administrative' },
                        ]"
                        option-label="label"
                        option-value="value"
                        fluid
                    />
                </div>

                <div v-if="form.related_type === 'content'" class="space-y-2">
                    <label>Conteúdo</label>
                    <Select v-model="form.content_id" :options="contents" option-label="title" option-value="id" show-clear fluid />
                </div>

                <div v-if="form.related_type === 'plan'" class="space-y-2">
                    <label>Plano</label>
                    <Select v-model="form.plan_id" :options="plans" option-label="title" option-value="id" show-clear fluid />
                </div>

                <div v-if="form.related_type === 'plan' && form.plan_id" class="space-y-2">
                    <label>Fase do plano</label>
                    <Select v-model="form.plan_phase_id" :options="phaseOptions" option-label="title" option-value="id" show-clear fluid />
                </div>

                <div class="space-y-2 md:col-span-2">
                    <label>Título</label>
                    <InputText v-model="form.title" fluid />
                </div>

                <div class="space-y-2 md:col-span-2">
                    <label>Descrição</label>
                    <AppSpeechTextareaAssist v-model="form.description" />
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
                    <label>Lembrete</label>
                    <DatePicker v-model="form.reminder_at" show-time hour-format="24" fluid />
                </div>

                <div class="space-y-2">
                    <label>Status</label>
                    <Select v-model="form.task_status_id" :options="statuses" option-label="name" option-value="id" fluid />
                </div>
            </div>

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
                <Button type="button" label="Cancelar" outlined severity="secondary" @click="closeModal" />
                <Button type="submit" :loading="form.processing" label="Salvar" icon="pi pi-save" />
            </div>
        </form>
    </Dialog>
</template>
