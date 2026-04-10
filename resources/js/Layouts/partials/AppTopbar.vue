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
</script>

<template>
    <header class="sticky top-0 z-30 border-b border-slate-200/70 bg-white/85 backdrop-blur dark:border-slate-800 dark:bg-slate-950/70">
        <div class="flex h-16 items-center justify-between gap-3 px-4 md:px-6">
            <div class="flex items-center gap-2">
                <Button
                    icon="pi pi-bars"
                    rounded
                    text
                    aria-label="Abrir menu"
                    class="md:hidden"
                    @click="emit('openMobileSidebar')"
                />
                <Button
                    :icon="collapsed ? 'pi pi-angle-right' : 'pi pi-angle-left'"
                    rounded
                    text
                    aria-label="Alternar barra lateral"
                    class="hidden md:flex"
                    @click="emit('toggleDesktopSidebar')"
                />
                <div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Workspace</p>
                    <h1 class="text-base font-semibold">Band Organizer</h1>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <Button icon="pi pi-bell" rounded text aria-label="Notificacoes" />
                <AppThemeSwitcher />
                <div class="hidden text-right sm:block">
                    <p class="text-sm font-semibold">{{ page.props.auth?.user?.name }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ page.props.auth?.user?.email }}</p>
                </div>
                <Link :href="route('logout')" method="post" as="button">
                    <Button icon="pi pi-sign-out" rounded outlined severity="secondary" aria-label="Sair" />
                </Link>
            </div>
        </div>
    </header>
</template>
