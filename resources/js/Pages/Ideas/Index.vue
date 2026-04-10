<script setup>
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppPagination from '@/Components/AppPagination.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    ideas: Object,
    filters: Object,
});

const executeIdea = (id) => router.post(route('ideas.execute', id));
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Ideias</h1>
            <Link :href="route('ideas.create')" class="rounded bg-slate-900 px-3 py-2 text-white dark:bg-slate-200 dark:text-slate-900">Nova ideia</Link>
        </div>

        <div class="space-y-3">
            <div v-for="idea in props.ideas.data" :key="idea.id" class="rounded border bg-white p-4 dark:bg-slate-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold">{{ idea.title }}</p>
                        <p class="text-sm text-slate-500">{{ idea.status }}</p>
                    </div>
                    <div class="flex gap-2">
                        <button class="rounded border px-2 py-1 text-sm" @click="executeIdea(idea.id)">Executar</button>
                        <Link :href="route('ideas.edit', idea.id)" class="rounded border px-2 py-1 text-sm">Editar</Link>
                    </div>
                </div>
            </div>
        </div>

        <AppPagination :links="props.ideas.links" />
    </div>
</template>
