<script setup>
import BoPriorityTag from '@/Components/ui/BoPriorityTag.vue';

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
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm transition-shadow hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
        <div class="mb-2 flex items-start justify-between gap-2">
            <p class="font-semibold leading-snug">{{ task.title }}</p>
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
            <span class="truncate text-xs text-slate-500 dark:text-slate-400">{{ task.assigned_user?.name || task.assignedUser?.name || 'Todos' }}</span>
        </div>

        <ProgressBar :value="completion()" style="height: 0.4rem" />
        <div class="mt-2 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
            <span>{{ task.subtasks?.filter((s) => s.completed).length || 0 }}/{{ task.subtasks?.length || 0 }} subtarefas</span>
            <span>{{ task.due_date || 'Sem prazo' }}</span>
        </div>
    </div>
</template>
