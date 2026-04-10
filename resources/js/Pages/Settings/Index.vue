<script setup>
import { onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

onMounted(() => {
    router.get(route('settings.pages.types'));
});
</script>

<template>
    <div class="p-4 text-sm text-slate-500">Redirecionando...</div>
</template>
