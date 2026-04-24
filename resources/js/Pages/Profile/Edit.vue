<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ProfileEditor from './Partials/ProfileEditor.vue';
import { Head, usePage } from '@inertiajs/vue3';

defineOptions({ layout: AppLayout });

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    notificationTypes: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const user = page.props.auth.user;
</script>

<template>
    <Head title="Perfil" />

    <ProfileEditor
        :user="user"
        :must-verify-email="props.mustVerifyEmail"
        :status="props.status"
        update-route-name="profile.update"
        password-route-name="profile.password.update"
        :show-email-verification="true"
        title="Perfil"
        :notification-types="props.notificationTypes"
    />
</template>
