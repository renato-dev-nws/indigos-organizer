<script setup>
import { computed } from 'vue';
import { Icon } from '@iconify/vue';
import { useTheme } from '@/Composables/useTheme';

const { currentTheme, setTheme } = useTheme();

const currentIcon = computed(() => {
    if (currentTheme.value === 'light') return 'ph:sun-bold';
    if (currentTheme.value === 'dark') return 'ph:moon-bold';
    return 'ph:monitor-bold';
});

const cycleTheme = () => {
    const order = ['system', 'light', 'dark'];
    const idx = order.indexOf(currentTheme.value);
    setTheme(order[(idx + 1) % order.length]);
};
</script>

<template>
    <div
        class="relative flex min-h-screen flex-col items-center justify-center bg-slate-50 p-4 dark:bg-slate-950"
    >
        <!-- Theme toggle -->
        <button
            type="button"
            class="fixed right-4 top-4 z-30 flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"
            :aria-label="'Tema: ' + currentTheme"
            :title="'Tema: ' + currentTheme"
            @click="cycleTheme"
        >
            <Icon :icon="currentIcon" class="h-[17px] w-[17px]" />
        </button>

        <!-- Logo / App name -->
        <div class="fixed inset-x-0 top-8 z-20 px-4">
            <div class="flex w-full flex-col items-center gap-3">
                <img src="/icons/io-icon-64x64.png" alt="Ícone Índigos Organizer" class="h-16 w-16 object-contain" />
                <p class="text-center text-lg font-bold text-slate-800 dark:text-slate-100">Índigos - Artist Organizer</p>
            </div>
        </div>

        <!-- Card -->
        <div
            class="mt-36 w-full max-w-sm overflow-hidden rounded-2xl bg-white px-6 py-6 shadow-lg ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800"
        >
            <slot />
        </div>
    </div>
</template>
