<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import ProfileEditor from '@/Pages/Profile/Partials/ProfileEditor.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const canEditAdmin = computed(() => !!page.props.auth?.user?.is_super_admin);
</script>

<template>
    <Head title="Editar usuario" />

    <ProfileEditor
        :user="props.user"
        update-route-name="users.update"
        :update-route-params="props.user.id"
        password-route-name="users.password.update"
        :password-route-params="props.user.id"
        title="Editar usuario"
        :can-edit-admin="canEditAdmin"
    />
</template>
