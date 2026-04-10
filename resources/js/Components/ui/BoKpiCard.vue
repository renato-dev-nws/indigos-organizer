<script setup>
import { Icon } from '@iconify/vue';

defineProps({
    label: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        default: 0,
    },
    icon: {
        type: String,
        default: 'ph:chart-bar-bold',
    },
    color: {
        type: String,
        default: 'indigo',
    },
});
</script>

<template>
    <Card>
        <template #content>
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ label }}</p>
                    <p class="mt-2 text-3xl font-bold tracking-tight">{{ value }}</p>
                </div>
                <div class="rounded-xl bg-indigo-500/10 p-3 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-400">
                    <Icon :icon="icon" class="h-5 w-5" />
                </div>
            </div>
        </template>
    </Card>
</template>
