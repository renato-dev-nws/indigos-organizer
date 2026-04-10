<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ task: Object, statuses: Array, contents: Array });
defineOptions({ layout: AppLayout });

const form = useForm({
    title: props.task.title,
    description: props.task.description,
    type: props.task.type,
    content_id: props.task.content_id,
    task_status_id: props.task.task_status_id,
    assignee: props.task.assignee,
    priority: props.task.priority,
    due_date: props.task.due_date,
});

const submit = () => form.put(route('tasks.update', props.task.id));
</script>

<template>
    <div class="space-y-4">
        <h1 class="text-2xl font-bold">Editar tarefa</h1>
        <form class="space-y-3" @submit.prevent="submit">
            <input v-model="form.title" class="w-full rounded border p-2" />
            <textarea v-model="form.description" class="w-full rounded border p-2" />
            <button class="rounded bg-slate-900 px-3 py-2 text-white" type="submit">Atualizar</button>
        </form>
    </div>
</template>
