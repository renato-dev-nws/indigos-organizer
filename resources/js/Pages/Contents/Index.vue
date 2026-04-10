<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppPagination from '@/Components/AppPagination.vue';

defineOptions({ layout: AppLayout });
const props = defineProps({ contents: Object });
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Conteudos</h1>
            <Link :href="route('contents.create')" class="rounded bg-slate-900 px-3 py-2 text-white dark:bg-slate-200 dark:text-slate-900">Novo conteudo</Link>
        </div>
        <div v-for="content in props.contents.data" :key="content.id" class="rounded border bg-white p-4 dark:bg-slate-800">
            <p class="font-semibold">{{ content.title }}</p>
            <p class="text-sm text-slate-500">{{ content.status }}</p>
        </div>
        <AppPagination :links="props.contents.links" />
    </div>
</template>
