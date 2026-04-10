<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

defineProps({
    summary: Object,
    nextContents: Array,
    urgentTasks: Array,
});
</script>

<template>
    <div class="space-y-6">
        <h1 class="text-2xl font-bold">Dashboard</h1>

        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded border bg-white p-4 dark:bg-slate-800">Ideias pendentes: {{ summary.pendingIdeas }}</div>
            <div class="rounded border bg-white p-4 dark:bg-slate-800">Conteudos da semana: {{ summary.contentsThisWeek }}</div>
            <div class="rounded border bg-white p-4 dark:bg-slate-800">Tarefas urgentes: {{ summary.urgentOpenTasks }}</div>
            <div class="rounded border bg-white p-4 dark:bg-slate-800">Casas de show: {{ summary.venuesCount }}</div>
        </div>

        <section>
            <h2 class="mb-2 text-lg font-semibold">Proximos conteudos</h2>
            <ul class="space-y-2">
                <li v-for="item in nextContents" :key="item.id" class="rounded border bg-white p-3 dark:bg-slate-800">{{ item.title }}</li>
            </ul>
        </section>

        <section>
            <h2 class="mb-2 text-lg font-semibold">Tarefas urgentes</h2>
            <ul class="space-y-2">
                <li v-for="item in urgentTasks" :key="item.id" class="rounded border bg-white p-3 dark:bg-slate-800">{{ item.title }}</li>
            </ul>
        </section>
    </div>
</template>
