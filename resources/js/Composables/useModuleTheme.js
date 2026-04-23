import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const MODULE_COMPONENT_MAP = [
    { key: 'tasks', match: (component) => component.startsWith('Tasks/') },
    { key: 'calendar', match: (component) => component.startsWith('Calendar/') },
    { key: 'ideas', match: (component) => component.startsWith('Ideas/') },
    { key: 'contents', match: (component) => component.startsWith('Contents/') },
    { key: 'plans', match: (component) => component.startsWith('Plans/') },
    { key: 'fast_notes', match: (component) => component.startsWith('FastNotes/') },
    { key: 'events', match: (component) => component.startsWith('Events/') },
    { key: 'venues', match: (component) => component.startsWith('Venues/') },
    { key: 'contacts', match: (component) => component.startsWith('Contacts/') },
    { key: 'shared_infos', match: (component) => component.startsWith('SharedInfos/') },
];

const COLOR_HEX = {
    'slate-400': '#94a3b8', 'slate-500': '#64748b', 'slate-600': '#475569',
    'gray-400': '#9ca3af', 'gray-500': '#6b7280', 'gray-600': '#4b5563',
    'zinc-400': '#a1a1aa', 'zinc-500': '#71717a', 'zinc-600': '#52525b',
    'neutral-400': '#a3a3a3', 'neutral-500': '#737373', 'neutral-600': '#525252',
    'stone-400': '#a8a29e', 'stone-500': '#78716c', 'stone-600': '#57534e',
    'red-400': '#f87171', 'red-500': '#ef4444', 'red-600': '#dc2626',
    'orange-400': '#fb923c', 'orange-500': '#f97316', 'orange-600': '#ea580c',
    'amber-400': '#fbbf24', 'amber-500': '#f59e0b', 'amber-600': '#d97706',
    'yellow-400': '#facc15', 'yellow-500': '#eab308', 'yellow-600': '#ca8a04',
    'lime-400': '#a3e635', 'lime-500': '#84cc16', 'lime-600': '#65a30d',
    'green-400': '#4ade80', 'green-500': '#22c55e', 'green-600': '#16a34a',
    'emerald-400': '#34d399', 'emerald-500': '#10b981', 'emerald-600': '#059669',
    'teal-400': '#2dd4bf', 'teal-500': '#14b8a6', 'teal-600': '#0d9488',
    'cyan-400': '#22d3ee', 'cyan-500': '#06b6d4', 'cyan-600': '#0891b2',
    'sky-400': '#38bdf8', 'sky-500': '#0ea5e9', 'sky-600': '#0284c7',
    'blue-400': '#60a5fa', 'blue-500': '#3b82f6', 'blue-600': '#2563eb',
    'indigo-400': '#818cf8', 'indigo-500': '#6366f1', 'indigo-600': '#4f46e5',
    'violet-400': '#a78bfa', 'violet-500': '#8b5cf6', 'violet-600': '#7c3aed',
    'purple-400': '#c084fc', 'purple-500': '#a855f7', 'purple-600': '#9333ea',
    'fuchsia-400': '#e879f9', 'fuchsia-500': '#d946ef', 'fuchsia-600': '#c026d3',
    'pink-400': '#f472b6', 'pink-500': '#ec4899', 'pink-600': '#db2777',
    'rose-400': '#fb7185', 'rose-500': '#f43f5e', 'rose-600': '#e11d48',
};

export function useModuleTheme() {
    const page = usePage();

    const moduleColors = computed(() => page.props.systemSettings?.module_colors || {});

    const getModuleKeyForPage = (component = page.component || '') => {
        const match = MODULE_COMPONENT_MAP.find((entry) => entry.match(component));
        return match?.key || null;
    };

    const getModuleColorToken = (moduleKey, fallback = 'slate-500') => {
        if (!moduleKey) {
            return fallback;
        }

        return moduleColors.value[moduleKey] || fallback;
    };

    const getModuleColorHex = (moduleKey, fallback = 'slate-500') => {
        const token = getModuleColorToken(moduleKey, fallback);
        return COLOR_HEX[token] || COLOR_HEX[fallback] || '#64748b';
    };

    return {
        moduleColors,
        getModuleKeyForPage,
        getModuleColorToken,
        getModuleColorHex,
    };
}
