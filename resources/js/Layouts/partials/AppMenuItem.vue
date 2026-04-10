<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Icon } from '@iconify/vue';

const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
    collapsed: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['navigate']);
const page = usePage();
const isOpen = ref(false);

const hasChildren = computed(() => Array.isArray(props.item.items) && props.item.items.length > 0);

// Detect if icon is an Iconify icon (contains ':') or a PrimeIcon class
const isIconify = (icon) => icon && icon.includes(':');

const isHrefActive = (href) => {
    if (!href) return false;

    const origin = typeof window !== 'undefined' ? window.location.origin : '';
    let normalized = href.replace(origin, '').split('?')[0];

    // Remove trailing slash (except root)
    if (normalized.length > 1) normalized = normalized.replace(/\/$/, '');

    // Get current path without query string
    const currentPath = page.url.split('?')[0].replace(/\/$/, '') || '/';

    if (normalized === '' || normalized === '/') {
        return currentPath === '/';
    }

    return currentPath === normalized || currentPath.startsWith(`${normalized}/`);
};

const isActive = computed(() => {
    if (hasChildren.value) {
        return props.item.items.some((child) => isHrefActive(child.href));
    }

    return isHrefActive(props.item.href);
});

const toggleChildren = () => {
    if (props.collapsed) return;
    isOpen.value = !isOpen.value;
};
</script>

<template>
    <div class="space-y-0.5">
        <button
            v-if="hasChildren"
            type="button"
            class="group flex w-full items-center gap-3 rounded-lg px-2.5 py-2 text-sm font-medium transition-all duration-150"
            :class="[
                isActive
                    ? 'bg-indigo-500/10 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300'
                    : 'text-slate-600 hover:bg-slate-200/60 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-800/60 dark:hover:text-slate-200',
                collapsed ? 'justify-center' : '',
            ]"
            @click="toggleChildren"
        >
            <Icon v-if="isIconify(item.icon)" :icon="item.icon" class="h-[17px] w-[17px] shrink-0" />
            <i v-else :class="['text-base shrink-0', item.icon]" />
            <span v-if="!collapsed" class="flex-1 text-left leading-none">{{ item.label }}</span>
            <Icon
                v-if="!collapsed"
                :icon="isOpen || isActive ? 'ph:caret-up-bold' : 'ph:caret-down-bold'"
                class="h-3 w-3 shrink-0 opacity-60"
            />
        </button>

        <Link
            v-else
            :href="item.href"
            class="group flex items-center gap-3 rounded-lg px-2.5 py-2 text-sm font-medium transition-all duration-150"
            :class="[
                isActive
                    ? 'bg-indigo-500/10 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300'
                    : 'text-slate-600 hover:bg-slate-200/60 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-800/60 dark:hover:text-slate-200',
                collapsed ? 'justify-center' : '',
            ]"
            @click="emit('navigate')"
        >
            <Icon v-if="isIconify(item.icon)" :icon="item.icon" class="h-[17px] w-[17px] shrink-0" />
            <i v-else :class="['text-base shrink-0', item.icon]" />
            <span v-if="!collapsed" class="leading-none">{{ item.label }}</span>
        </Link>

        <div v-if="hasChildren && !collapsed && (isOpen || isActive)" class="mt-0.5 space-y-0.5 pl-7">
            <Link
                v-for="child in item.items"
                :key="child.label"
                :href="child.href"
                class="flex items-center gap-2 rounded-lg px-2.5 py-[7px] text-[13px] font-medium transition-all duration-150"
                :class="isHrefActive(child.href)
                    ? 'bg-indigo-500/10 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300'
                    : 'text-slate-500 hover:bg-slate-200/60 hover:text-slate-700 dark:text-slate-500 dark:hover:bg-slate-800/60 dark:hover:text-slate-300'"
                @click="emit('navigate')"
            >
                <Icon v-if="isIconify(child.icon)" :icon="child.icon" class="h-3.5 w-3.5 shrink-0 opacity-70" />
                <i v-else :class="[child.icon || 'pi pi-circle-fill', 'text-[10px] shrink-0']" />
                <span class="leading-none">{{ child.label }}</span>
            </Link>
        </div>
    </div>
</template>
