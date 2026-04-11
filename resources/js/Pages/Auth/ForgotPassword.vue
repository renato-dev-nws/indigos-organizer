<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({ status: String });
const form = useForm({ email: '' });
const submit = () => form.post(route('password.email'));
</script>

<template>
    <GuestLayout>
        <Head title="Forgot Password" />
        <p class="mb-4 text-sm text-slate-600 dark:text-slate-300">Informe seu email para receber o link de recuperação.</p>
        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">{{ status }}</div>
        <form class="space-y-4" @submit.prevent="submit">
            <div class="space-y-2"><label class="text-sm font-medium">Email</label><InputText v-model="form.email" type="email" class="w-full" /><Message v-if="form.errors.email" severity="error" size="small" variant="simple">{{ form.errors.email }}</Message></div>
            <div class="flex justify-end"><Button type="submit" label="Enviar link" :loading="form.processing" /></div>
        </form>
    </GuestLayout>
</template>
