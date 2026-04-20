<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({
    visible: { type: Boolean, default: false },
    task: { type: Object, default: null },
});

const emit = defineEmits(['update:visible']);
const confirm = useConfirm();

const assigneeLabel = computed(() => {
    const assignees = props.task?.assigned_users || props.task?.assignedUsers || [];
    if (!assignees.length) {
        return 'Todos';
    }

    return assignees.map((user) => user.name).join(', ');
});

const formatDateTime = (value) => {
    if (!value) {
        return '-';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    return date.toLocaleString('pt-BR');
};

const formatDate = (value) => {
    if (!value) {
        return '-';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    return date.toLocaleDateString('pt-BR');
};

const priorityLabels = {
    low: 'Baixa',
    medium: 'Média',
    high: 'Alta',
    urgent: 'Urgente',
};

const priorityLabel = computed(() => {
    const value = props.task?.priority;
    return priorityLabels[value] || value || '-';
});

const relationLabel = computed(() => {
    if (!props.task?.related_type) {
        return '';
    }

    if (props.task.related_type === 'content' && props.task.content?.title) {
        return `Conteúdo: ${props.task.content.title}`;
    }

    if (props.task.related_type === 'plan' && props.task.plan?.title) {
        const phase = props.task.planPhase?.title ? ` / Fase: ${props.task.planPhase.title}` : '';
        return `Planejamento: ${props.task.plan.title}${phase}`;
    }

    if (props.task.related_type === 'event' && props.task.event?.title) {
        return `Evento: ${props.task.event.title}`;
    }

    if (props.task.related_type === 'administrative') {
        return 'Administrativo';
    }

    return '';
});

const isCompleted = computed(() => {
    const statusName = String(props.task?.status?.name || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase();

    return ['conclu', 'finaliz', 'done', 'completed'].some((token) => statusName.includes(token));
});

const completeTask = () => {
    if (!props.task?.id || isCompleted.value) {
        return;
    }

    confirm.require({
        header: 'Confirmar ação',
        message: 'Confirma concluir esta tarefa?',
        icon: 'pi pi-question-circle',
        rejectLabel: 'Cancelar',
        acceptLabel: 'Confirmar',
        acceptClass: 'p-button-sm',
        rejectClass: 'p-button-text p-button-sm',
        accept: () => {
            router.patch(route('tasks.quick-action', props.task.id), { action: 'complete' }, { preserveScroll: true, replace: true });
            emit('update:visible', false);
        },
    });
};
</script>

<template>
    <Dialog :visible="visible" modal header="Detalhes da tarefa" :style="{ width: '42rem', maxWidth: '96vw' }" @update:visible="emit('update:visible', $event)">
        <div v-if="task" class="space-y-4">
            <div>
                <h3 class="text-lg font-semibold">{{ task.title }}</h3>
                <p class="text-sm text-slate-500">{{ task.description || 'Sem descrição.' }}</p>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <p class="text-xs text-slate-500">Status</p>
                    <p class="font-medium">{{ task.status?.name || '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Responsáveis</p>
                    <p class="font-medium">{{ assigneeLabel }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Prioridade</p>
                    <p class="font-medium">{{ priorityLabel }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Agendado para</p>
                    <p class="font-medium">{{ formatDateTime(task.scheduled_for) }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Prazo</p>
                    <p class="font-medium">{{ formatDate(task.due_date) }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Lembrete</p>
                    <p class="font-medium">{{ formatDateTime(task.reminder_at) }}</p>
                </div>
                <div v-if="relationLabel" class="md:col-span-2">
                    <p class="text-xs text-slate-500">Relacionamento</p>
                    <p class="font-medium">{{ relationLabel }}</p>
                </div>
            </div>

            <Card>
                <template #title>Subtarefas</template>
                <template #content>
                    <ul class="list-inside list-disc space-y-1 text-sm">
                        <li v-for="subtask in task.subtasks || []" :key="subtask.id || subtask.title">
                            {{ subtask.title }}
                        </li>
                        <li v-if="!(task.subtasks || []).length">Nenhuma subtarefa.</li>
                    </ul>
                </template>
            </Card>
        </div>

        <template #footer>
            <div class="flex w-full items-center justify-between gap-2">
                <div>
                    <Link v-if="task?.id" :href="route('tasks.edit', task.id)">
                        <Button label="EDITAR" icon="pi pi-pencil" outlined severity="secondary" />
                    </Link>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        v-if="task && !isCompleted"
                        label="Marcar como concluída"
                        icon="pi pi-check"
                        severity="success"
                        @click="completeTask"
                    />
                    <Button label="Fechar" outlined severity="secondary" @click="emit('update:visible', false)" />
                </div>
            </div>
        </template>
    </Dialog>
</template>
