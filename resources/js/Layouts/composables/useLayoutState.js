import { computed, reactive } from 'vue';
import { usePage } from '@inertiajs/vue3';

const layoutState = reactive({
    desktopCollapsed: false,
    mobileSidebarVisible: false,
});

export function useLayoutState() {
    const page = usePage();

    const currentPath = computed(() => page.url || '/');

    const toggleDesktopSidebar = () => {
        layoutState.desktopCollapsed = !layoutState.desktopCollapsed;
    };

    const openMobileSidebar = () => {
        layoutState.mobileSidebarVisible = true;
    };

    const closeMobileSidebar = () => {
        layoutState.mobileSidebarVisible = false;
    };

    return {
        layoutState,
        currentPath,
        toggleDesktopSidebar,
        openMobileSidebar,
        closeMobileSidebar,
    };
}
