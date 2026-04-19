<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
    supratitle: {
        type: String,
        default: '',
    },
    icon: {
        type: String,
        default: '',
    },
});

const page = usePage();
const shouldShowSubtitle = computed(() => page.component === 'Dashboard' && !!props.subtitle);
</script>

<template>
    <div class="flex items-start justify-between gap-3 rounded-2xl bg-white/80 p-4 shadow-sm ring-1 ring-slate-200/70 dark:bg-slate-900/70 dark:ring-slate-800">
        <div class="min-w-0">
            <p v-if="supratitle" class="text-sm text-slate-500 dark:text-slate-400">{{ supratitle }}</p>
            <h1 class="flex items-center gap-2 text-2xl font-bold my-1 py-0">
                <span v-if="icon" class="shrink-0 -mb-[0.5rem]"><iconify-icon :icon="icon" width="28" height="28" /></span>
                {{ title }}
            </h1>
            <p v-if="shouldShowSubtitle" class="text-sm text-slate-500 dark:text-slate-400">{{ subtitle }}</p>
        </div>
        <div class="bo-page-header-actions flex items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
