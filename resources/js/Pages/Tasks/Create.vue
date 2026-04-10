<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ statuses: Array, contents: Array });

defineOptions({ layout: AppLayout });

const form = useForm({
    title: '',
    description: '',
    type: 'content',
    content_id: null,
    task_status_id: props.statuses?.[0]?.id,
    assignee: '',
    priority: 'medium',
    due_date: '',
});

const submit = () => form.post(route('tasks.store'));
</script>

<template>
    <div class="space-y-4">
        <h1 class="text-2xl font-bold">Nova tarefa</h1>
        <form class="space-y-3" @submit.prevent="submit">
            <input v-model="form.title" class="w-full rounded border p-2" placeholder="Titulo" />
            <textarea v-model="form.description" class="w-full rounded border p-2" placeholder="Descricao" />
            <button class="rounded bg-slate-900 px-3 py-2 text-white" type="submit">Salvar</button>
        </form>
    </div>
</template>
