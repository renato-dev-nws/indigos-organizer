<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });
defineProps({ idea: Object });
</script>

<template>
    <div class="space-y-4">
        <h1 class="text-2xl font-bold">{{ idea.title }}</h1>
        <p>{{ idea.description }}</p>
        <ul class="space-y-2">
            <li v-for="ref in idea.references" :key="ref.id" class="rounded border bg-white p-3 dark:bg-slate-800">
                <a :href="ref.url" target="_blank" class="underline">{{ ref.title }}</a>
            </li>
        </ul>
    </div>
</template>
