<script setup>
import { computed } from 'vue';

const props = defineProps({
    visible: { type: Boolean, default: false },
    task: { type: Object, default: null },
});

const emit = defineEmits(['update:visible']);

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
                    <p class="font-medium">{{ task.priority }}</p>
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
                <div>
                    <p class="text-xs text-slate-500">Evento</p>
                    <p class="font-medium">{{ task.event?.title || '-' }}</p>
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
            <Button label="Fechar" outlined severity="secondary" @click="emit('update:visible', false)" />
        </template>
    </Dialog>
</template>
