import { ref, onMounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const STORAGE_KEY = 'bo-theme';
const VALID_THEMES = ['light', 'dark', 'system'];

const normalizeTheme = (theme) => (VALID_THEMES.includes(theme) ? theme : 'system');

// Shared reactive ref so all composable instances stay in sync
const currentTheme = ref(
    typeof window !== 'undefined' ? normalizeTheme(localStorage.getItem(STORAGE_KEY)) : 'system',
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
        const normalizedTheme = normalizeTheme(theme);
        const previousTheme = normalizeTheme(currentTheme.value);

        if (previousTheme === normalizedTheme) {
            applyTheme(normalizedTheme);
            return;
        }

        currentTheme.value = normalizedTheme;
        localStorage.setItem(STORAGE_KEY, normalizedTheme);
        applyTheme(normalizedTheme);

        // Persist to server only when authenticated
        const page = usePage();
        if (page.props.auth?.user) {
            const serverTheme = normalizeTheme(page.props.auth?.user?.theme);
            if (serverTheme === normalizedTheme) {
                return;
            }

            router.put('/settings/theme', { theme: normalizedTheme }, { preserveState: true, preserveScroll: true, replace: true });
        }
    };

    onMounted(() => {
        const stored = localStorage.getItem(STORAGE_KEY);
        const page = usePage();
        const serverTheme = page.props.auth?.user?.theme;

        if (!stored && serverTheme) {
            // First session: sync server preference → localStorage
            const normalizedServerTheme = normalizeTheme(serverTheme);
            localStorage.setItem(STORAGE_KEY, normalizedServerTheme);
            currentTheme.value = normalizedServerTheme;
        }

        applyTheme(currentTheme.value);
    });

    return { currentTheme, setTheme, applyTheme };
}
