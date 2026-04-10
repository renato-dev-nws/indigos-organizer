<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const form = useForm({
    title: '',
    script: '',
    status: 'queued',
    content_category_ids: [],
    links: [],
    planned_publish_at: '',
    published_at: '',
});

const submit = () => form.post(route('contents.store'));
</script>

<template>
    <div class="space-y-4">
        <h1 class="text-2xl font-bold">Novo conteudo</h1>
        <form class="space-y-3" @submit.prevent="submit">
            <input v-model="form.title" class="w-full rounded border p-2" placeholder="Titulo" />
            <textarea v-model="form.script" class="w-full rounded border p-2" placeholder="Roteiro" />
            <button class="rounded bg-slate-900 px-3 py-2 text-white" type="submit">Salvar</button>
        </form>
    </div>
</template>
