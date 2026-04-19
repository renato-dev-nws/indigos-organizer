<script setup>
import { computed, onMounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import AppTopbar from './partials/AppTopbar.vue';
import AppSidebar from './partials/AppSidebar.vue';
import { useLayoutState } from './composables/useLayoutState';
import { usePushSubscription } from './composables/usePushSubscription';

const page = usePage();
const toast = useToast();
useConfirm();

const {
    layoutState,
    toggleDesktopSidebar,
    openMobileSidebar,
    closeMobileSidebar,
} = useLayoutState();

const { subscribe } = usePushSubscription();
onMounted(() => subscribe());

const menuItems = computed(() => [
    { label: 'Dashboard', icon: 'ph:squares-four-bold', href: route('dashboard') },
    { label: 'Tarefas', icon: 'mdi:checkbox-multiple-outline', href: route('tasks.index') },
    { label: 'Calendário', icon: 'mdi:calendar-month-outline', href: route('calendar.index') },
    { label: 'Ideias', icon: 'mdi:lightbulb-multiple-outline', href: route('ideas.index') },
    { label: 'Conteúdos', icon: 'mdi:film-reel', href: route('contents.index') },
    { label: 'Planejamentos', icon: 'mdi:routes-clock', href: route('plans.index') },
    { label: 'Notas Rápidas', icon: 'mdi:notebook-edit-outline', href: route('fast-notes.index') },
    { label: 'Eventos', icon: 'ph:ticket-bold', href: route('events.index') },
    { label: 'Locais', icon: 'mdi:map-marker-multiple-outline', href: route('venues.index') },
    { label: 'Contatos', icon: 'ph:address-book-bold', href: route('contacts.index') },
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
                <div class="mx-auto max-w-[95%]">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
