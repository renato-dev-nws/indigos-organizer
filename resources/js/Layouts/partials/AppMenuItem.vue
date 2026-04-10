<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

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

const isActive = computed(() => {
    if (!props.item.href) {
        return false;
    }

    const origin = typeof window !== 'undefined' ? window.location.origin : '';
    const normalized = props.item.href.replace(origin, '');
    return page.url === normalized || page.url.startsWith(`${normalized}/`);
});
</script>

<template>
    <Link
        :href="item.href"
        class="group flex items-center gap-3 rounded-xl px-3 py-2 text-sm transition-colors"
        :class="[
            isActive
                ? 'bg-cyan-500/15 text-cyan-700 dark:text-cyan-300'
                : 'text-slate-700 hover:bg-slate-200/60 dark:text-slate-300 dark:hover:bg-slate-800',
            collapsed ? 'justify-center' : '',
        ]"
        @click="emit('navigate')"
    >
        <i :class="['text-base', item.icon]"></i>
        <span v-if="!collapsed">{{ item.label }}</span>
    </Link>
</template>
