<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({ canResetPassword: Boolean, status: String });

const form = useForm({ email: '', password: '', remember: false });
const submit = () => form.post(route('login'), { onFinish: () => form.reset('password') });
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">{{ status }}</div>

        <form class="space-y-4" @submit.prevent="submit">
            <div class="space-y-2">
                <label for="email" class="text-sm font-medium">Email</label>
                <InputText id="email" v-model="form.email" type="email" autocomplete="username" class="w-full" :invalid="!!form.errors.email" />
                <Message v-if="form.errors.email" severity="error" size="small" variant="simple">{{ form.errors.email }}</Message>
            </div>

            <div class="space-y-2">
                <label for="password" class="text-sm font-medium">Senha</label>
                <Password id="password" v-model="form.password" autocomplete="current-password" :feedback="false" toggle-mask fluid :invalid="!!form.errors.password" />
                <Message v-if="form.errors.password" severity="error" size="small" variant="simple">{{ form.errors.password }}</Message>
            </div>

            <div class="flex items-center gap-2">
                <Checkbox v-model="form.remember" binary input-id="remember" />
                <label for="remember" class="text-sm text-slate-600 dark:text-slate-300">Lembrar de mim</label>
            </div>

            <div class="flex items-center justify-between">
                <Link v-if="canResetPassword" :href="route('password.request')" class="text-sm text-slate-600 underline dark:text-slate-300">Esqueceu a senha?</Link>
                <Button type="submit" label="Entrar" :loading="form.processing" />
            </div>
        </form>
    </GuestLayout>
</template>
