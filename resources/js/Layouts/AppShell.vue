<script setup>
import { computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import AppTopbar from './partials/AppTopbar.vue';
import AppSidebar from './partials/AppSidebar.vue';
import { useLayoutState } from './composables/useLayoutState';

const page = usePage();
const toast = useToast();
useConfirm();

const {
    layoutState,
    toggleDesktopSidebar,
    openMobileSidebar,
    closeMobileSidebar,
} = useLayoutState();

const menuItems = computed(() => [
    { label: 'Dashboard', icon: 'pi pi-home', href: route('dashboard') },
    { label: 'Ideias', icon: 'pi pi-lightbulb', href: route('ideas.index') },
    { label: 'Conteúdos', icon: 'pi pi-video', href: route('contents.index') },
    { label: 'Tarefas', icon: 'pi pi-check-square', href: route('tasks.index') },
    { label: 'Casas de Show', icon: 'pi pi-building', href: route('venues.index') },
    { label: 'Usuários', icon: 'pi pi-users', href: route('users.index') },
    {
        label: 'Configurações',
        icon: 'pi pi-cog',
        items: [
            { label: 'Tipos de ideia', icon: 'pi pi-tag', href: route('settings.pages.idea-types') },
            { label: 'Categorias de ideia', icon: 'pi pi-bookmark', href: route('settings.pages.idea-categories') },
            { label: 'Plataformas', icon: 'pi pi-mobile', href: route('settings.pages.content-platforms') },
            { label: 'Tipos de conteúdo', icon: 'pi pi-clone', href: route('settings.pages.content-types') },
            { label: 'Categorias de conteúdo', icon: 'pi pi-palette', href: route('settings.pages.content-categories') },
            { label: 'Status de tarefas', icon: 'pi pi-sort-alt', href: route('settings.pages.task-statuses') },
        ],
    },
]);

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            toast.add({ severity: 'success', summary: 'Sucesso', detail: flash.success, life: 3500 });
        }

        if (flash?.error) {
            toast.add({ severity: 'error', summary: 'Erro', detail: flash.error, life: 4500 });
        }
    },
    { immediate: true, deep: true },
);
</script>

<template>
    <div class="min-h-screen text-slate-900 transition-colors dark:text-slate-100">
        <Toast />
        <ConfirmDialog />

        <AppSidebar
            :items="menuItems"
            :collapsed="layoutState.desktopCollapsed"
            :mobile-visible="layoutState.mobileSidebarVisible"
            @close-mobile="closeMobileSidebar"
        />

        <div
            class="min-h-screen transition-all"
            :class="layoutState.desktopCollapsed ? 'md:pl-20' : 'md:pl-72'"
        >
            <AppTopbar
                :collapsed="layoutState.desktopCollapsed"
                @toggle-desktop-sidebar="toggleDesktopSidebar"
                @open-mobile-sidebar="openMobileSidebar"
            />

            <main class="bo-shell-content p-4 md:p-6">
                <div class="mx-auto max-w-[1400px]">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
