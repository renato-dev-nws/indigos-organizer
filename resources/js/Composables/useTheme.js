import { ref, onMounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const STORAGE_KEY = 'bo-theme';

// Shared reactive ref so all composable instances stay in sync
const currentTheme = ref(
    typeof window !== 'undefined' ? (localStorage.getItem(STORAGE_KEY) ?? 'system') : 'system',
);

export function useTheme() {
    const applyTheme = (theme) => {
        if (typeof window === 'undefined') return;
        const html = document.documentElement;
        html.classList.remove('app-dark');
        const isDark =
            theme === 'dark' ||
            (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        if (isDark) html.classList.add('app-dark');
    };

    const setTheme = (theme) => {
        currentTheme.value = theme;
        localStorage.setItem(STORAGE_KEY, theme);
        applyTheme(theme);
        // Persist to server only when authenticated
        const page = usePage();
        if (page.props.auth?.user) {
            router.put('/settings/theme', { theme }, { preserveState: true, preserveScroll: true });
        }
    };

    onMounted(() => {
        const stored = localStorage.getItem(STORAGE_KEY);
        const page = usePage();
        const serverTheme = page.props.auth?.user?.theme;

        if (!stored && serverTheme) {
            // First session: sync server preference → localStorage
            localStorage.setItem(STORAGE_KEY, serverTheme);
            currentTheme.value = serverTheme;
        }

        applyTheme(currentTheme.value);
    });

    return { currentTheme, setTheme, applyTheme };
}
