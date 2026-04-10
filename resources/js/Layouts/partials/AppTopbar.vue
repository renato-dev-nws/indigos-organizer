<script setup>
import { usePage, Link } from '@inertiajs/vue3';
import AppThemeSwitcher from '@/Components/AppThemeSwitcher.vue';

defineProps({
    collapsed: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['toggleDesktopSidebar', 'openMobileSidebar']);
const page = usePage();

const handleMenuClick = () => {
    if (window.innerWidth < 768) {
        emit('openMobileSidebar');
        return;
    }

    emit('toggleDesktopSidebar');
};
</script>

<template>
    <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 backdrop-blur dark:border-slate-700 dark:bg-slate-950/80">
        <div class="flex h-16 items-center justify-between gap-3 px-4 md:px-6">
            <div class="flex items-center gap-2">
                <Button
                    :icon="collapsed ? 'pi pi-angle-right' : 'pi pi-angle-left'"
                    rounded
                    text
                    aria-label="Alternar menu"
                    @click="handleMenuClick"
                />
                <div>
                    <h1 class="text-base font-semibold">Band Organizer</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Painel de operação</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <AppThemeSwitcher />
                <div class="hidden text-right sm:block">
                    <p class="text-sm font-semibold">{{ page.props.auth?.user?.name }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ page.props.auth?.user?.email }}</p>
                </div>
                <Link :href="route('logout')" method="post" as="button">
                    <Button icon="pi pi-sign-out" rounded text severity="secondary" aria-label="Sair" />
                </Link>
            </div>
        </div>
    </header>
</template>
