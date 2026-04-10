<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const form = useForm({
    name: '',
    email: '',
    phone: '',
    contact_name: '',
    venue_size_id: null,
    instagram_url: '',
    facebook_url: '',
    youtube_url: '',
    website_url: '',
    notes: '',
    description: '',
});

const submit = () => form.post(route('venues.store'));
</script>

<template>
    <div class="space-y-4">
        <h1 class="text-2xl font-bold">Nova casa de show</h1>
        <form class="space-y-3" @submit.prevent="submit">
            <input v-model="form.name" class="w-full rounded border p-2" placeholder="Nome" />
            <button class="rounded bg-slate-900 px-3 py-2 text-white" type="submit">Salvar</button>
        </form>
    </div>
</template>
