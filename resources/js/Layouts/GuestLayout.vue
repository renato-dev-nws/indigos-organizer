<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
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

const page = usePage();
const logoUrl = computed(() => page.props.systemSettings?.logo_url ?? null);
const isInlineSvg = computed(() => typeof logoUrl.value === 'string' && logoUrl.value.trim().startsWith('<svg'));
</script>

<template>
    <div
        class="relative flex min-h-screen flex-col items-center justify-center bg-slate-50 p-4 dark:bg-slate-950"
    >
        <!-- Theme toggle -->
        <button
            type="button"
            class="absolute right-4 top-4 flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"
            :aria-label="'Tema: ' + currentTheme"
            :title="'Tema: ' + currentTheme"
            @click="cycleTheme"
        >
            <Icon :icon="currentIcon" class="h-[17px] w-[17px]" />
        </button>

        <!-- Logo / App name -->
        <div class="mb-6 flex flex-col items-center gap-3">
            <template v-if="logoUrl">
                <div v-if="isInlineSvg" class="h-16 w-auto" v-html="logoUrl" />
                <img v-else :src="logoUrl" alt="Logo" class="h-16 w-auto object-contain" />
                <span class="text-xs text-slate-400 dark:text-slate-500">Band Organizer</span>
            </template>
            <template v-else>
                <div
                    class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-700 shadow-lg shadow-indigo-500/30"
                >
                    <Icon icon="ph:music-notes-bold" class="h-7 w-7 text-white" />
                </div>
                <p class="text-lg font-bold text-slate-800 dark:text-slate-100">Band Organizer</p>
            </template>
        </div>

        <!-- Card -->
        <div
            class="w-full max-w-sm overflow-hidden rounded-2xl bg-white px-6 py-6 shadow-lg ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800"
        >
            <slot />
        </div>
    </div>
</template>
