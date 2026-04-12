<script setup>
import { computed, watch, watchEffect } from 'vue';
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
    { label: 'Dashboard', icon: 'ph:squares-four-bold', href: route('dashboard') },
    { label: 'Tarefas', icon: 'ph:check-square-bold', href: route('tasks.index') },
    { label: 'Ideias', icon: 'ph:lightbulb-bold', href: route('ideas.index') },
    { label: 'Conteúdos', icon: 'ph:video-camera-bold', href: route('contents.index') },
    { label: 'Planejamentos', icon: 'ph:map-trifold-bold', href: route('plans.index') },
    { label: 'Locais', icon: 'ph:music-notes-bold', href: route('venues.index') },
    { label: 'Informações Úteis', icon: 'ph:info-bold', href: route('shared-infos.index') },
    {
        label: 'Configurações',
        icon: 'ph:gear-six-bold',
        items: [
            { label: 'Tipos', icon: 'ph:tag-bold', href: route('settings.pages.types') },
            { label: 'Categorias', icon: 'ph:bookmark-bold', href: route('settings.pages.categories') },
            { label: 'Estilos', icon: 'ph:music-notes-simple-bold', href: route('settings.pages.styles') },
            { label: 'Plataformas', icon: 'ph:device-mobile-bold', href: route('settings.pages.content-platforms') },
            { label: 'Status de tarefas', icon: 'ph:sort-ascending-bold', href: route('settings.pages.task-statuses') },
            { label: 'Sistema', icon: 'ph:sliders-bold', href: route('settings.pages.system') },
        ],
    },
    { label: 'Usuários', icon: 'ph:users-three-bold', href: route('users.index') },
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

// Dynamically update the browser favicon when a system icon is set
watchEffect(() => {
    const iconUrl = page.props.systemSettings?.icon_url;
    if (iconUrl) {
        let link = /** @type {HTMLLinkElement|null} */ (document.querySelector("link[rel='icon']"));
        if (!link) {
            link = document.createElement('link');
            link.rel = 'icon';
            document.head.appendChild(link);
        }
        link.href = iconUrl;
    }
});
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
            class="min-h-screen transition-all duration-300"
            :class="layoutState.desktopCollapsed ? 'md:pl-[72px]' : 'md:pl-64'"
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
