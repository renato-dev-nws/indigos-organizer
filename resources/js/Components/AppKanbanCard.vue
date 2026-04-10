<script setup>
import BoPriorityTag from '@/Components/ui/BoPriorityTag.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    task: {
        type: Object,
        required: true,
    },
});

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
            <Link :href="route('tasks.edit', task.id)">
                <Button icon="pi pi-pencil" text rounded size="small" aria-label="Editar tarefa" />
            </Link>
        </div>

        <p v-if="task.content?.title" class="mb-2 truncate text-xs text-slate-500 dark:text-slate-400">
            Conteúdo: {{ task.content.title }}
        </p>

        <div class="mb-2 flex items-center justify-between gap-2">
            <BoPriorityTag :value="task.priority" />
            <span class="truncate text-xs text-slate-500 dark:text-slate-400">{{ task.assignee || 'Sem responsável' }}</span>
        </div>

        <ProgressBar :value="completion()" style="height: 0.4rem" />
        <div class="mt-2 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
            <span>{{ task.subtasks?.filter((s) => s.completed).length || 0 }}/{{ task.subtasks?.length || 0 }} subtarefas</span>
            <span>{{ task.due_date || 'Sem prazo' }}</span>
        </div>
    </div>
</template>
