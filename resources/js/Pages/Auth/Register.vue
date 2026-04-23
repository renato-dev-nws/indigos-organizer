<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });
const submit = () => form.post(route('register'), { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <GuestLayout>
        <Head title="Configuração inicial" />

        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300">
            Este cadastro cria o primeiro usuário do sistema como super administrador.
        </div>

        <form class="space-y-4" @submit.prevent="submit">
            <div class="space-y-2"><label class="text-sm font-medium">Nome</label><InputText v-model="form.name" class="w-full" /><Message v-if="form.errors.name" severity="error" size="small" variant="simple">{{ form.errors.name }}</Message></div>
            <div class="space-y-2"><label class="text-sm font-medium">Email</label><InputText v-model="form.email" type="email" class="w-full" /><Message v-if="form.errors.email" severity="error" size="small" variant="simple">{{ form.errors.email }}</Message></div>
            <div class="space-y-2"><label class="text-sm font-medium">Senha</label><Password v-model="form.password" toggle-mask :feedback="false" fluid /><Message v-if="form.errors.password" severity="error" size="small" variant="simple">{{ form.errors.password }}</Message></div>
            <div class="space-y-2"><label class="text-sm font-medium">Confirmar senha</label><Password v-model="form.password_confirmation" toggle-mask :feedback="false" fluid /><Message v-if="form.errors.password_confirmation" severity="error" size="small" variant="simple">{{ form.errors.password_confirmation }}</Message></div>
            <div class="flex items-center justify-between"><Link :href="route('login')" class="text-sm text-slate-600 underline dark:text-slate-300">Já tem conta?</Link><Button type="submit" label="Cadastrar" :loading="form.processing" /></div>
        </form>
    </GuestLayout>
</template>
