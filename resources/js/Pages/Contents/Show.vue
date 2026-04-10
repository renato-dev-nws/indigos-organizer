<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });
defineProps({ content: Object });
</script>

<template>
    <div class="space-y-4">
        <h1 class="text-2xl font-bold">{{ content.title }}</h1>
        <div class="rounded border bg-white p-4 dark:bg-slate-800" v-html="content.script" />
    </div>
</template>
