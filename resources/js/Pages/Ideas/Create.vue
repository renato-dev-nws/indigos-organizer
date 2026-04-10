<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const form = useForm({
    title: '',
    description: '',
    idea_type_id: null,
    idea_category_id: null,
    status: 'pending',
    references: [],
});

const submit = () => form.post(route('ideas.store'));
</script>

<template>
    <div class="space-y-4">
        <h1 class="text-2xl font-bold">Nova ideia</h1>
        <form class="space-y-3" @submit.prevent="submit">
            <input v-model="form.title" class="w-full rounded border p-2" placeholder="Titulo" />
            <textarea v-model="form.description" class="w-full rounded border p-2" placeholder="Descricao" />
            <select v-model="form.status" class="w-full rounded border p-2">
                <option value="pending">pending</option>
                <option value="maturing">maturing</option>
                <option value="cancelled">cancelled</option>
                <option value="in_production">in_production</option>
                <option value="executed">executed</option>
            </select>
            <button class="rounded bg-slate-900 px-3 py-2 text-white" type="submit">Salvar</button>
        </form>
    </div>
</template>
