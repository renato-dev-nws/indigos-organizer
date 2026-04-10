<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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

const isHrefActive = (href) => {
    if (!href) {
        return false;
    }

    const origin = typeof window !== 'undefined' ? window.location.origin : '';
    const normalized = href.replace(origin, '');
    return page.url === normalized || page.url.startsWith(`${normalized}/`);
};

const isActive = computed(() => {
    if (hasChildren.value) {
        return props.item.items.some((child) => isHrefActive(child.href));
    }

    return isHrefActive(props.item.href);
});

const toggleChildren = () => {
    if (props.collapsed) {
        return;
    }

    isOpen.value = !isOpen.value;
};
</script>

<template>
    <div class="space-y-1">
        <button
            v-if="hasChildren"
            type="button"
            class="group flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm transition-colors"
            :class="[
                isActive
                    ? 'bg-indigo-500/15 text-indigo-700 dark:text-indigo-300'
                    : 'text-slate-700 hover:bg-slate-200/70 dark:text-slate-300 dark:hover:bg-slate-800',
                collapsed ? 'justify-center' : '',
            ]"
            @click="toggleChildren"
        >
            <i :class="['text-base', item.icon]" />
            <span v-if="!collapsed" class="flex-1 text-left">{{ item.label }}</span>
            <i v-if="!collapsed" :class="['pi text-xs', isOpen ? 'pi-chevron-up' : 'pi-chevron-down']" />
        </button>

        <Link
            v-else
            :href="item.href"
            class="group flex items-center gap-3 rounded-xl px-3 py-2 text-sm transition-colors"
            :class="[
                isActive
                    ? 'bg-indigo-500/15 text-indigo-700 dark:text-indigo-300'
                    : 'text-slate-700 hover:bg-slate-200/70 dark:text-slate-300 dark:hover:bg-slate-800',
                collapsed ? 'justify-center' : '',
            ]"
            @click="emit('navigate')"
        >
            <i :class="['text-base', item.icon]" />
            <span v-if="!collapsed">{{ item.label }}</span>
        </Link>

        <div v-if="hasChildren && !collapsed && (isOpen || isActive)" class="space-y-1 pl-6">
            <Link
                v-for="child in item.items"
                :key="child.label"
                :href="child.href"
                class="flex items-center gap-2 rounded-lg px-2.5 py-2 text-xs transition-colors"
                :class="isHrefActive(child.href)
                    ? 'bg-indigo-500/15 text-indigo-700 dark:text-indigo-300'
                    : 'text-slate-600 hover:bg-slate-200/70 dark:text-slate-400 dark:hover:bg-slate-800'"
                @click="emit('navigate')"
            >
                <i :class="[child.icon || 'pi pi-circle-fill', 'text-[10px]']" />
                <span>{{ child.label }}</span>
            </Link>
        </div>
    </div>
</template>
