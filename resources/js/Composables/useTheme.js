import { onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

export function useTheme() {
    const applyTheme = (theme) => {
        const html = document.documentElement;
        html.classList.remove('app-dark');

        if (theme === 'dark') {
            html.classList.add('app-dark');
        } else if (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            html.classList.add('app-dark');
        }
    };

    const setTheme = (theme) => {
        router.put('/settings/theme', { theme }, { preserveState: true, preserveScroll: true });
        applyTheme(theme);
    };

    onMounted(() => {
        const theme = usePage().props.auth?.user?.theme ?? 'system';
        applyTheme(theme);
    });

    return { setTheme, applyTheme };
}
