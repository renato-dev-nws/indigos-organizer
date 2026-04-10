<script setup>
import { Icon } from '@iconify/vue';
import AppMenu from './AppMenu.vue';

defineProps({
    items: {
        type: Array,
        default: () => [],
    },
    collapsed: {
        type: Boolean,
        default: false,
    },
    mobileVisible: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['closeMobile']);
</script>

<template>
    <aside
        class="fixed inset-y-0 left-0 z-40 hidden border-r border-slate-200/60 bg-white/95 px-3 py-4 backdrop-blur-xl md:block dark:border-slate-800/60 dark:bg-slate-950/95"
        :class="collapsed ? 'w-[72px]' : 'w-64'"
    >
        <div class="mb-5 flex items-center gap-2.5 px-2">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-700 shadow-md shadow-indigo-500/30">
                <Icon icon="ph:music-notes-bold" class="h-4 w-4 text-white" />
            </div>
            <div v-if="!collapsed">
                <p class="text-sm font-bold leading-tight text-slate-800 dark:text-slate-100">Band Organizer</p>
                <p class="text-[10px] text-slate-400 dark:text-slate-500">Painel colaborativo</p>
            </div>
        </div>

        <AppMenu :items="items" :collapsed="collapsed" />
    </aside>

    <Drawer
        :visible="mobileVisible"
        position="left"
        class="md:hidden"
        @update:visible="emit('closeMobile')"
    >
        <template #header>
            <div class="flex items-center gap-2.5">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-700 shadow-md shadow-indigo-500/30">
                    <Icon icon="ph:music-notes-bold" class="h-4 w-4 text-white" />
                </div>
                <div>
                    <p class="text-sm font-bold leading-tight">Band Organizer</p>
                    <p class="text-[10px] text-slate-400">Painel colaborativo</p>
                </div>
            </div>
        </template>
        <AppMenu :items="items" @navigate="emit('closeMobile')" />
    </Drawer>
</template>
