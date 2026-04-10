<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useTheme } from '@/Composables/useTheme';
import { Icon } from '@iconify/vue';

const page = usePage();
const { setTheme } = useTheme();

const currentTheme = computed(() => page.props.auth?.user?.theme ?? 'system');

const themes = [
    { key: 'light', icon: 'ph:sun-bold', label: 'Tema claro' },
    { key: 'dark', icon: 'ph:moon-bold', label: 'Tema escuro' },
    { key: 'system', icon: 'ph:monitor-bold', label: 'Tema do sistema' },
];
</script>

<template>
    <div class="flex items-center gap-0.5 rounded-lg border border-slate-200/80 bg-slate-100/60 p-0.5 dark:border-slate-700/80 dark:bg-slate-800/60">
        <button
            v-for="t in themes"
            :key="t.key"
            type="button"
            class="rounded-md p-1.5 transition-all duration-150"
            :class="currentTheme === t.key
                ? 'bg-white text-indigo-600 shadow-sm dark:bg-slate-700 dark:text-indigo-400'
                : 'text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300'"
            :aria-label="t.label"
            :title="t.label"
            @click="setTheme(t.key)"
        >
            <Icon :icon="t.icon" class="h-[15px] w-[15px]" />
        </button>
    </div>
</template>
