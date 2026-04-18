<script setup>
import { computed, ref } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import AppNotificationBell from '@/Components/AppNotificationBell.vue';
import { useTheme } from '@/Composables/useTheme';

defineProps({
    collapsed: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['toggleDesktopSidebar', 'openMobileSidebar']);
const page = usePage();
const iconUrl = computed(() => page.props.systemSettings?.icon_url ?? null);
const isInlineSvgIcon = computed(() => typeof iconUrl.value === 'string' && iconUrl.value.trim().startsWith('<svg'));
const avatarUrl = computed(() => page.props.auth?.user?.avatar_url ?? '');
const userMenu = ref(null);
const { currentTheme, setTheme } = useTheme();

const themeOptions = [
    { key: 'light', icon: 'ph:sun-bold', label: 'Claro' },
    { key: 'dark', icon: 'ph:moon-bold', label: 'Escuro' },
    { key: 'system', icon: 'ph:monitor-bold', label: 'Sistema' },
];

const handleMenuClick = () => {
    if (window.innerWidth < 768) {
        emit('openMobileSidebar');
        return;
    }

    emit('toggleDesktopSidebar');
};

const toggleUserMenu = (event) => userMenu.value?.toggle(event);

const pickTheme = (theme) => {
    setTheme(theme);
};
</script>

<template>
    <header class="bo-topbar sticky top-0 z-30 border-b border-slate-200/60 bg-white/90 backdrop-blur-md dark:border-slate-800/60 dark:bg-slate-950/90">
        <div class="flex h-14 items-center justify-between gap-3 px-4 md:px-5">
            <div class="flex items-center gap-2.5">
                <button
                    type="button"
                    class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                    :aria-label="collapsed ? 'Expandir menu' : 'Recolher menu'"
                    @click="handleMenuClick"
                >
                    <Icon icon="ph:list-bold" class="h-[18px] w-[18px]" />
                </button>

                <!-- Logo visível apenas no mobile -->
                <Link :href="route('dashboard')" class="flex items-center gap-2 md:hidden">
                    <div v-if="iconUrl && isInlineSvgIcon" class="h-7 w-7 rounded-md" v-html="iconUrl" />
                    <img v-else-if="iconUrl" :src="iconUrl" alt="Ícone" class="h-7 w-7 rounded-md object-contain" />
                    <div
                        v-else
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-gradient-to-br from-indigo-500 to-indigo-700"
                    >
                        <Icon icon="ph:music-notes-bold" class="h-3.5 w-3.5 text-white" />
                    </div>
                </Link>
            </div>

            <div class="flex items-center gap-2">
                <Link :href="route('fast-notes.index', { open_create: 1 })" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700 dark:text-slate-500 dark:hover:bg-slate-800 dark:hover:text-slate-200" aria-label="Nova nota rápida" title="Nova nota rápida">
                    <Icon icon="mdi:notebook-plus" class="h-[17px] w-[17px]" />
                </Link>
                <AppNotificationBell />
                <div class="hidden text-right sm:block">
                    <p class="text-sm font-semibold leading-tight text-slate-800 dark:text-slate-200">{{ page.props.auth?.user?.name }}</p>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500">{{ page.props.auth?.user?.email }}</p>
                </div>
                <button
                    type="button"
                    class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full border border-slate-200 bg-slate-100 text-xs font-semibold text-slate-700 transition-colors hover:border-slate-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:border-slate-600"
                    aria-label="Menu do usuário"
                    title="Menu do usuário"
                    @click="toggleUserMenu"
                >
                    <img v-if="avatarUrl" :src="avatarUrl" alt="Avatar do usuário" class="h-full w-full object-cover" />
                    <Icon v-else icon="mdi:person" class="h-[18px] w-[18px]" />
                </button>

                <Popover ref="userMenu">
                    <div class="flex min-w-[260px] flex-col gap-3 p-2">
                        <div class="flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-700">
                            <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full border border-slate-200 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                <img v-if="avatarUrl" :src="avatarUrl" alt="Avatar do usuário" class="h-full w-full object-cover" />
                                <Icon v-else icon="mdi:person" class="h-5 w-5" />
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ page.props.auth?.user?.name }}</p>
                                <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ page.props.auth?.user?.email }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-1 rounded-lg border border-slate-200 p-1 dark:border-slate-700">
                            <button
                                v-for="theme in themeOptions"
                                :key="theme.key"
                                type="button"
                                class="flex flex-col items-center gap-1 rounded-md px-2 py-2 text-[11px] font-medium transition"
                                :class="currentTheme === theme.key
                                    ? 'bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-400'
                                    : 'text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100'"
                                @click="pickTheme(theme.key)"
                            >
                                <Icon :icon="theme.icon" class="h-4 w-4" />
                                <span>{{ theme.label }}</span>
                            </button>
                        </div>

                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="flex w-full items-center justify-center gap-2 rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50 dark:border-red-900/50 dark:text-red-400 dark:hover:bg-red-950/40"
                        >
                            <Icon icon="ph:sign-out-bold" class="h-[16px] w-[16px]" />
                            <span>Sair</span>
                        </Link>
                    </div>
                </Popover>
            </div>
        </div>
    </header>
</template>
