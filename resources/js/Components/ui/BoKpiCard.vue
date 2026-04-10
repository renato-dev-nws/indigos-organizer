<script setup>
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
        default: 'pi pi-chart-bar',
    },
});
</script>

<template>
    <Card>
        <template #content>
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ label }}</p>
                    <p class="mt-2 text-3xl font-bold">{{ value }}</p>
                </div>
                <div class="rounded-xl bg-cyan-500/15 p-3 text-cyan-600 dark:text-cyan-300">
                    <i :class="[icon, 'text-xl']"></i>
                </div>
            </div>
        </template>
    </Card>
</template>
