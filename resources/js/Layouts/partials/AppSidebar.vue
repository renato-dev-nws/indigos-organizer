<script setup>
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
        class="fixed inset-y-0 left-0 z-40 hidden border-r border-slate-200/80 bg-slate-50/90 px-3 py-4 backdrop-blur md:block dark:border-slate-700 dark:bg-slate-900/90"
        :class="collapsed ? 'w-20' : 'w-72'"
    >
        <div class="mb-6 flex items-center gap-3 px-2">
            <div class="h-9 w-9 rounded-lg bg-indigo-600 text-center text-xl font-black leading-9 text-white">B</div>
            <div v-if="!collapsed">
                <p class="font-semibold">Band Organizer</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Painel colaborativo</p>
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
            <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-lg bg-indigo-600 text-center text-xl font-black leading-9 text-white">B</div>
                <div>
                    <p class="font-semibold">Band Organizer</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Painel colaborativo</p>
                </div>
            </div>
        </template>
        <AppMenu :items="items" @navigate="emit('closeMobile')" />
    </Drawer>
</template>
