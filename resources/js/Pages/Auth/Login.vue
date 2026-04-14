<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Icon } from '@iconify/vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({ canResetPassword: Boolean, status: String });

const page = usePage();
const form = useForm({ email: '', password: '', remember: false });

const submit = () => form.post(route('login'), { onFinish: () => form.reset('password') });
</script>

<template>
    <GuestLayout>
        <Head title="Entrar" />

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">{{ status }}</div>
        <Message v-if="page.props.errors?.oauth" severity="error" size="small" class="mb-4">{{ page.props.errors.oauth }}</Message>

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

        <div class="my-5 flex items-center gap-3">
            <div class="h-px flex-1 bg-slate-200 dark:bg-slate-700" />
            <span class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">Ou</span>
            <div class="h-px flex-1 bg-slate-200 dark:bg-slate-700" />
        </div>

        <div class="space-y-3">
            <a
                :href="route('auth.google.redirect')"
                class="flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
            >
                <Icon icon="logos:google-icon" class="h-4 w-4" />
                <span>Entrar com Google</span>
            </a>

            <p class="text-xs text-slate-500 dark:text-slate-400">
                Google somente para usuarios Gmail ja cadastrados no sistema.
            </p>
        </div>
    </GuestLayout>
</template>
