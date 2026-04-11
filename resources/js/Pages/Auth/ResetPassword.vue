<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ email: String, token: String });
const form = useForm({ token: props.token, email: props.email, password: '', password_confirmation: '' });
const submit = () => form.post(route('password.store'), { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <GuestLayout>
        <Head title="Reset Password" />
        <form class="space-y-4" @submit.prevent="submit">
            <div class="space-y-2"><label class="text-sm font-medium">Email</label><InputText v-model="form.email" type="email" class="w-full" /><Message v-if="form.errors.email" severity="error" size="small" variant="simple">{{ form.errors.email }}</Message></div>
            <div class="space-y-2"><label class="text-sm font-medium">Senha</label><Password v-model="form.password" :feedback="false" toggle-mask fluid /><Message v-if="form.errors.password" severity="error" size="small" variant="simple">{{ form.errors.password }}</Message></div>
            <div class="space-y-2"><label class="text-sm font-medium">Confirmar senha</label><Password v-model="form.password_confirmation" :feedback="false" toggle-mask fluid /><Message v-if="form.errors.password_confirmation" severity="error" size="small" variant="simple">{{ form.errors.password_confirmation }}</Message></div>
            <div class="flex justify-end"><Button type="submit" label="Redefinir senha" :loading="form.processing" /></div>
        </form>
    </GuestLayout>
</template>
