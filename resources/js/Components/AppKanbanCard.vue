<script setup>
import BoPriorityTag from '@/Components/ui/BoPriorityTag.vue';
import BoDateText from '@/Components/ui/BoDateText.vue';

const props = defineProps({
    task: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['view', 'edit']);

const completion = () => {
    const done = props.task.subtasks?.filter((s) => s.completed).length || 0;
    const total = props.task.subtasks?.length || 0;

    if (total === 0) return 0;
    return Math.round((done / total) * 100);
};

const taskIsCompleted = () => {
    const statusName = String(props.task?.status?.name || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase();

    return ['conclu', 'finaliz', 'done', 'completed'].some((token) => statusName.includes(token));
};

const isTaskOverdue = () => {
    if (!props.task?.due_date || props.task?.archived || taskIsCompleted()) {
        return false;
    }

    const dueDate = new Date(`${props.task.due_date}T23:59:59`);
    if (Number.isNaN(dueDate.getTime())) {
        return false;
    }

    return dueDate.getTime() < Date.now();
};

const assigneeLabel = () => {
    const assignees = props.task?.assigned_users || props.task?.assignedUsers || [];
    if (!assignees.length) {
        return 'Todos';
    }

    return assignees.map((user) => user.name).join(', ');
};
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm transition-shadow hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
        <div class="mb-2 flex items-start justify-between gap-2">
            <p class="flex items-center gap-1 font-semibold leading-snug">
                <iconify-icon v-if="isTaskOverdue()" icon="mdi:clock-alert" class="text-red-500" width="14" height="14" />
                <span>{{ task.title }}</span>
            </p>
            <div class="flex items-center gap-1">
                <Button icon="pi pi-eye" text rounded size="small" aria-label="Visualizar tarefa" @click="emit('view', task)" />
                <Button icon="pi pi-pencil" text rounded size="small" aria-label="Editar tarefa" @click="emit('edit', task)" />
            </div>
        </div>

        <p v-if="task.content?.title" class="mb-2 truncate text-xs text-slate-500 dark:text-slate-400">
            Conteúdo: {{ task.content.title }}
        </p>
        <p v-else-if="task.event?.title" class="mb-2 truncate text-xs text-slate-500 dark:text-slate-400">
            Evento: {{ task.event.title }}
        </p>
        <p v-else-if="task.planPhase?.title" class="mb-2 truncate text-xs text-slate-500 dark:text-slate-400">
            Fase: {{ task.planPhase.title }}
        </p>
        <p v-else-if="task.plan?.title" class="mb-2 truncate text-xs text-slate-500 dark:text-slate-400">
            Plano: {{ task.plan.title }}
        </p>

        <div class="mb-2 flex items-center justify-between gap-2">
            <BoPriorityTag :value="task.priority" />
            <span class="truncate text-xs text-slate-500 dark:text-slate-400">{{ assigneeLabel() }}</span>
        </div>

        <ProgressBar :value="completion()" style="height: 0.4rem" />
        <div class="mt-2 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
            <span>{{ task.subtasks?.filter((s) => s.completed).length || 0 }}/{{ task.subtasks?.length || 0 }} subtarefas</span>
            <span><BoDateText :value="task.due_date" mode="date" fallback="Sem prazo" /></span>
        </div>
    </div>
</template>
