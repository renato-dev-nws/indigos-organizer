<script setup>
import { computed } from 'vue';
import { Icon } from '@iconify/vue';
import { Link, usePage } from '@inertiajs/vue3';
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

const page = usePage();
const logoUrl = computed(() => page.props.systemSettings?.logo_url ?? null);
const iconUrl = computed(() => page.props.systemSettings?.icon_url ?? null);
</script>

<template>
    <aside
        class="bo-sidebar fixed inset-y-0 left-0 z-40 hidden border-r border-slate-200/60 bg-white/95 px-3 py-4 backdrop-blur-xl md:block dark:border-slate-800/60 dark:bg-slate-950/95"
        :class="collapsed ? 'w-[72px]' : 'w-64'"
    >
        <Link :href="route('dashboard')" class="mb-5 flex items-center gap-2.5 px-2">
            <!-- Collapsed: show icon or gradient fallback -->
            <template v-if="collapsed">
                <img
                    v-if="iconUrl"
                    :src="iconUrl"
                    alt="Ícone"
                    class="h-8 w-8 shrink-0 rounded-lg object-contain"
                />
                <div
                    v-else
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-700 shadow-md shadow-indigo-500/30"
                >
                    <Icon icon="ph:music-notes-bold" class="h-4 w-4 text-white" />
                </div>
            </template>

            <!-- Expanded: show logo or gradient + text -->
            <template v-else>
                <img
                    v-if="logoUrl"
                    :src="logoUrl"
                    alt="Logo"
                    class="h-9 max-w-[180px] object-contain"
                />
                <template v-else>
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-700 shadow-md shadow-indigo-500/30"
                    >
                        <Icon icon="ph:music-notes-bold" class="h-4 w-4 text-white" />
                    </div>
                    <div>
                        <p class="text-sm font-bold leading-tight text-slate-800 dark:text-slate-100">Índigos - Artist Organizer</p>
                        <p class="text-[10px] text-slate-400 dark:text-slate-500">Painel colaborativo</p>
                    </div>
                </template>
            </template>
        </Link>

        <AppMenu :items="items" :collapsed="collapsed" />
    </aside>

    <Drawer
        :visible="mobileVisible"
        position="left"
        class="md:hidden"
        @update:visible="emit('closeMobile')"
    >
        <template #header>
            <Link :href="route('dashboard')" class="flex items-center gap-2.5" @click="emit('closeMobile')">
                <img
                    v-if="iconUrl"
                    :src="iconUrl"
                    alt="Ícone"
                    class="h-8 w-8 rounded-lg object-contain"
                />
                <div
                    v-else
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-700 shadow-md shadow-indigo-500/30"
                >
                    <Icon icon="ph:music-notes-bold" class="h-4 w-4 text-white" />
                </div>
                <div>
                    <p class="text-sm font-bold leading-tight">Índigos - Artist Organizer</p>
                    <p class="text-[10px] text-slate-400">Painel colaborativo</p>
                </div>
            </Link>
        </template>
        <AppMenu :items="items" @navigate="emit('closeMobile')" />
    </Drawer>
</template>
