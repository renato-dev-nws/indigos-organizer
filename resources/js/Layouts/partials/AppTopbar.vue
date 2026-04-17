<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import AppThemeSwitcher from '@/Components/AppThemeSwitcher.vue';
import AppNotificationBell from '@/Components/AppNotificationBell.vue';

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
const userInitial = computed(() => {
    const name = String(page.props.auth?.user?.name ?? '').trim();
    return name ? name.charAt(0).toUpperCase() : '?';
});

const handleMenuClick = () => {
    if (window.innerWidth < 768) {
        emit('openMobileSidebar');
        return;
    }

    emit('toggleDesktopSidebar');
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
                <AppThemeSwitcher />
                <Link :href="route('fast-notes.index', { open_create: 1 })" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700 dark:text-slate-500 dark:hover:bg-slate-800 dark:hover:text-slate-200" aria-label="Nova nota rápida" title="Nova nota rápida">
                    <Icon icon="mdi:note-plus-outline" class="h-[17px] w-[17px]" />
                </Link>
                <AppNotificationBell />
                <div class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full border border-slate-200 bg-slate-100 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    <img v-if="avatarUrl" :src="avatarUrl" alt="Avatar do usuário" class="h-full w-full object-cover" />
                    <span v-else>{{ userInitial }}</span>
                </div>
                <div class="hidden text-right sm:block">
                    <p class="text-sm font-semibold leading-tight text-slate-800 dark:text-slate-200">{{ page.props.auth?.user?.name }}</p>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500">{{ page.props.auth?.user?.email }}</p>
                </div>
                <Link :href="route('logout')" method="post" as="button">
                    <button
                        type="button"
                        class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-red-50 hover:text-red-500 dark:text-slate-500 dark:hover:bg-red-950/40 dark:hover:text-red-400"
                        aria-label="Sair"
                        title="Sair"
                    >
                        <Icon icon="ph:sign-out-bold" class="h-[17px] w-[17px]" />
                    </button>
                </Link>
            </div>
        </div>
    </header>
</template>
