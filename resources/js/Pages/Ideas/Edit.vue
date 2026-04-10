<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({ idea: Object });

const form = useForm({
    title: props.idea.title,
    description: props.idea.description,
    idea_type_id: props.idea.idea_type_id,
    idea_category_id: props.idea.idea_category_id,
    status: props.idea.status,
    references: props.idea.references ?? [],
});

const submit = () => form.put(route('ideas.update', props.idea.id));
</script>

<template>
    <div class="space-y-4">
        <h1 class="text-2xl font-bold">Editar ideia</h1>
        <form class="space-y-3" @submit.prevent="submit">
            <input v-model="form.title" class="w-full rounded border p-2" />
            <textarea v-model="form.description" class="w-full rounded border p-2" />
            <button class="rounded bg-slate-900 px-3 py-2 text-white" type="submit">Atualizar</button>
        </form>
    </div>
</template>
