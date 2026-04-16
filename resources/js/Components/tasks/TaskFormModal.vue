<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppSpeechTextareaAssist from '@/Components/AppSpeechTextareaAssist.vue';

const props = defineProps({
    visible: { type: Boolean, default: false },
    task: { type: Object, default: null },
    statuses: { type: Array, default: () => [] },
    contents: { type: Array, default: () => [] },
    plans: { type: Array, default: () => [] },
    events: { type: Array, default: () => [] },
    users: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:visible']);

const form = useForm({
    related_type: 'administrative',
    content_id: null,
    plan_id: null,
    plan_phase_id: null,
    event_id: null,
    title: '',
    description: '',
    assigned_user_id: null,
    priority: 'medium',
    archived: false,
    scheduled_for: '',
    due_date: '',
    reminder_at: '',
    task_status_id: null,
    subtasks: [],
});

const selectedPlan = computed(() => props.plans?.find((plan) => plan.id === form.plan_id));
const phaseOptions = computed(() => selectedPlan.value?.phases ?? []);
const completedStatusIds = computed(() =>
    (props.statuses || [])
        .filter((status) => {
            const name = String(status?.name || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase();

            return ['conclu', 'finaliz', 'done', 'completed'].some((token) => name.includes(token));
        })
        .map((status) => status.id),
);
const canArchive = computed(() => completedStatusIds.value.includes(form.task_status_id));
const hydrating = ref(false);

const closeModal = () => emit('update:visible', false);

const hydrateForm = () => {
    hydrating.value = true;

    if (!props.task) {
        form.defaults({
            related_type: 'administrative',
            content_id: null,
            plan_id: null,
            plan_phase_id: null,
            event_id: null,
            title: '',
            description: '',
            assigned_user_id: null,
            priority: 'medium',
            archived: false,
            scheduled_for: '',
            due_date: '',
            reminder_at: '',
            task_status_id: props.statuses?.[0]?.id ?? null,
            subtasks: [],
        });
        form.reset();
        hydrating.value = false;
        return;
    }

    form.defaults({
        related_type: props.task.related_type,
        content_id: props.task.content_id,
        plan_id: props.task.plan_id,
        plan_phase_id: props.task.plan_phase_id,
        event_id: props.task.event_id,
        title: props.task.title,
        description: props.task.description,
        assigned_user_id: props.task.assigned_user_id,
        priority: props.task.priority,
        archived: !!props.task.archived,
        scheduled_for: props.task.scheduled_for,
        due_date: props.task.due_date,
        reminder_at: props.task.reminder_at,
        task_status_id: props.task.task_status_id,
        subtasks: props.task.subtasks || [],
    });
    form.reset();
    hydrating.value = false;
};

watch(() => props.visible, (isVisible) => {
    if (isVisible) {
        hydrateForm();
    }
});

watch(() => form.related_type, () => {
    if (hydrating.value) {
        return;
    }

    form.content_id = null;
    form.plan_id = null;
    form.plan_phase_id = null;
    form.event_id = null;
});

watch(() => form.plan_id, () => {
    if (hydrating.value) {
        return;
    }

    form.plan_phase_id = null;
});

watch(canArchive, (value) => {
    if (!value) {
        form.archived = false;
    }
});

watch(() => form.scheduled_for, (value) => {
    if (hydrating.value || !value) {
        return;
    }

    const scheduledDate = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(scheduledDate.getTime())) {
        return;
    }

    form.reminder_at = new Date(scheduledDate.getTime() - (60 * 60 * 1000));
});

const addSubtask = () => form.subtasks.push({ title: '', completed: false, order: form.subtasks.length + 1 });
const removeSubtask = (index) => form.subtasks.splice(index, 1);

const submit = () => {
    if (props.task?.id) {
        form.put(route('tasks.update', props.task.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
            },
        });
        return;
    }

    form.post(route('tasks.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
        },
    });
};
</script>

<template>
    <Dialog :visible="visible" modal :header="task?.id ? 'Editar tarefa' : 'Nova tarefa'" :style="{ width: '56rem', maxWidth: '96vw' }" @update:visible="emit('update:visible', $event)">
        <form class="space-y-4" @submit.prevent="submit">
            <Message v-if="form.hasErrors" severity="error" size="small" variant="simple">
                {{ Object.values(form.errors)[0] }}
            </Message>

            <div class="grid gap-3 md:grid-cols-2">
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
                        :invalid="!!form.errors.related_type"
                        fluid
                    />
                    <Message v-if="form.errors.related_type" severity="error" size="small" variant="simple">{{ form.errors.related_type }}</Message>
                </div>

                <div v-if="form.related_type === 'content'" class="space-y-2">
                    <label>Conteúdo</label>
                    <Select v-model="form.content_id" :options="contents" option-label="title" option-value="id" show-clear :invalid="!!form.errors.content_id" fluid />
                    <Message v-if="form.errors.content_id" severity="error" size="small" variant="simple">{{ form.errors.content_id }}</Message>
                </div>

                <div v-if="form.related_type === 'plan'" class="space-y-2">
                    <label>Plano</label>
                    <Select v-model="form.plan_id" :options="plans" option-label="title" option-value="id" show-clear :invalid="!!form.errors.plan_id" fluid />
                    <Message v-if="form.errors.plan_id" severity="error" size="small" variant="simple">{{ form.errors.plan_id }}</Message>
                </div>

                <div v-if="form.related_type === 'plan' && form.plan_id" class="space-y-2">
                    <label>Fase do plano</label>
                    <Select v-model="form.plan_phase_id" :options="phaseOptions" option-label="title" option-value="id" show-clear :invalid="!!form.errors.plan_phase_id" fluid />
                    <Message v-if="form.errors.plan_phase_id" severity="error" size="small" variant="simple">{{ form.errors.plan_phase_id }}</Message>
                </div>

                <div v-if="form.related_type === 'event'" class="space-y-2">
                    <label>Evento</label>
                    <Select v-model="form.event_id" :options="events" option-label="title" option-value="id" show-clear :invalid="!!form.errors.event_id" fluid />
                    <Message v-if="form.errors.event_id" severity="error" size="small" variant="simple">{{ form.errors.event_id }}</Message>
                </div>

                <div class="space-y-2 md:col-span-2">
                    <label>Título</label>
                    <InputText v-model="form.title" :invalid="!!form.errors.title" fluid />
                    <Message v-if="form.errors.title" severity="error" size="small" variant="simple">{{ form.errors.title }}</Message>
                </div>

                <div class="space-y-2 md:col-span-2">
                    <label>Descrição</label>
                    <AppSpeechTextareaAssist v-model="form.description" />
                    <Textarea v-model="form.description" rows="4" fluid />
                </div>

                <div class="space-y-2">
                    <label>Responsável</label>
                    <Select v-model="form.assigned_user_id" :options="[{ id: null, name: 'Todos' }, ...users]" option-label="name" option-value="id" show-clear :invalid="!!form.errors.assigned_user_id" fluid />
                    <Message v-if="form.errors.assigned_user_id" severity="error" size="small" variant="simple">{{ form.errors.assigned_user_id }}</Message>
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
                        :invalid="!!form.errors.priority"
                        fluid
                    />
                    <Message v-if="form.errors.priority" severity="error" size="small" variant="simple">{{ form.errors.priority }}</Message>
                </div>

                <div class="space-y-2">
                    <label>Agendado para</label>
                    <DatePicker v-model="form.scheduled_for" show-time hour-format="24" show-clear fluid />
                    <Message v-if="form.errors.scheduled_for" severity="error" size="small" variant="simple">{{ form.errors.scheduled_for }}</Message>
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
                    <Select v-model="form.task_status_id" :options="statuses" option-label="name" option-value="id" :invalid="!!form.errors.task_status_id" fluid />
                    <Message v-if="form.errors.task_status_id" severity="error" size="small" variant="simple">{{ form.errors.task_status_id }}</Message>
                </div>

                <div v-if="canArchive" class="space-y-2">
                    <label>Arquivamento</label>
                    <div class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-700">
                        <Checkbox v-model="form.archived" binary input-id="task-archived" />
                        <label for="task-archived" class="text-sm">Arquivar</label>
                    </div>
                </div>
            </div>

            <Card>
                <template #title>Subtarefas</template>
                <template #content>
                    <div class="space-y-2">
                        <div v-for="(subtask, index) in form.subtasks" :key="index" class="flex items-center gap-2">
                            <Checkbox v-model="subtask.completed" binary :input-id="`subtask-completed-${index}`" />
                            <InputText
                                v-model="subtask.title"
                                class="w-full"
                                placeholder="Título da subtarefa"
                                :class="{ 'line-through opacity-70': subtask.completed }"
                            />
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
