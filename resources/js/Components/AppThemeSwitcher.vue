<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useTheme } from '@/Composables/useTheme';

const page = usePage();
const { setTheme } = useTheme();

const currentTheme = computed(() => page.props.auth?.user?.theme ?? 'system');
</script>

<template>
    <div class="flex items-center gap-2">
        <button
            v-for="option in ['light', 'dark', 'system']"
            :key="option"
            class="rounded border px-2 py-1 text-xs"
            :class="currentTheme === option ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900' : 'bg-white text-slate-700 dark:bg-slate-800 dark:text-slate-200'"
            @click="setTheme(option)"
        >
            {{ option }}
        </button>
    </div>
</template>
