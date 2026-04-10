<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppPagination from '@/Components/AppPagination.vue';

defineOptions({ layout: AppLayout });
const props = defineProps({ tasks: Object, statuses: Array });
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Tarefas</h1>
            <Link :href="route('tasks.create')" class="rounded bg-slate-900 px-3 py-2 text-white dark:bg-slate-200 dark:text-slate-900">Nova tarefa</Link>
        </div>

        <div v-for="task in props.tasks.data" :key="task.id" class="rounded border bg-white p-4 dark:bg-slate-800">
            <p class="font-semibold">{{ task.title }}</p>
            <p class="text-sm text-slate-500">{{ task.priority }} | {{ task.status?.name }}</p>
        </div>

        <AppPagination :links="props.tasks.links" />
    </div>
</template>
