<script setup>
import { computed, ref } from 'vue';
import { useTheme } from '@/Composables/useTheme';
import { Icon } from '@iconify/vue';

const { currentTheme, setTheme } = useTheme();
const popover = ref(null);

const currentIcon = computed(() => {
    if (currentTheme.value === 'light') return 'ph:sun-bold';
    if (currentTheme.value === 'dark') return 'ph:moon-bold';
    return 'ph:monitor-bold';
});

const themes = [
    { key: 'light', icon: 'ph:sun-bold', label: 'Claro' },
    { key: 'dark', icon: 'ph:moon-bold', label: 'Escuro' },
    { key: 'system', icon: 'ph:monitor-bold', label: 'Sistema' },
];

const toggle = (event) => popover.value?.toggle(event);

const pick = (key) => {
    setTheme(key);
    popover.value?.hide();
};
</script>

<template>
    <button
        type="button"
        class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"
        aria-label="Selecionar tema"
        title="Selecionar tema"
        @click="toggle"
    >
        <Icon :icon="currentIcon" class="h-[17px] w-[17px]" />
    </button>

    <Popover ref="popover">
        <div class="flex items-center gap-1 p-1">
            <button
                v-for="t in themes"
                :key="t.key"
                type="button"
                class="flex flex-col items-center gap-1 rounded-lg px-3 py-2 text-[11px] font-medium transition-all duration-150"
                :class="currentTheme === t.key
                    ? 'bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-400'
                    : 'text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200'"
                :aria-label="t.label"
                @click="pick(t.key)"
            >
                <Icon :icon="t.icon" class="h-4 w-4" />
                <span>{{ t.label }}</span>
            </button>
        </div>
    </Popover>
</template>
