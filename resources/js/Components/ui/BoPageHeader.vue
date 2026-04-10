<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="flex flex-col gap-4 rounded-2xl bg-white/80 p-4 shadow-sm ring-1 ring-slate-200/70 md:flex-row md:items-center md:justify-between dark:bg-slate-900/70 dark:ring-slate-800">
        <div>
            <h1 class="text-2xl font-bold">{{ title }}</h1>
            <p v-if="subtitle" class="text-sm text-slate-500 dark:text-slate-400">{{ subtitle }}</p>
        </div>
        <div class="flex items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
