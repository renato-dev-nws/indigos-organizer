<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineOptions({ layout: AppLayout });

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profile" />

    <div class="space-y-6">
        <div class="rounded-2xl bg-white/80 p-4 shadow-sm ring-1 ring-slate-200/70 dark:bg-slate-900/70 dark:ring-slate-800">
            <h2 class="text-xl font-semibold">Perfil</h2>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70 dark:bg-slate-900 dark:ring-slate-800">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-xl"
                />
            </div>

            <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70 dark:bg-slate-900 dark:ring-slate-800">
                <UpdatePasswordForm class="max-w-xl" />
            </div>

            <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70 dark:bg-slate-900 dark:ring-slate-800 lg:col-span-2">
                <DeleteUserForm class="max-w-xl" />
            </div>
        </div>
    </div>
</template>
