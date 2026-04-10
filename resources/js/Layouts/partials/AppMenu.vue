<script setup>
import AppMenuItem from './AppMenuItem.vue';

defineProps({
    items: {
        type: Array,
        default: () => [],
    },
    collapsed: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['navigate']);
</script>

<template>
    <nav class="space-y-1">
        <AppMenuItem
            v-for="item in items"
            :key="item.label"
            :item="item"
            :collapsed="collapsed"
            @navigate="emit('navigate')"
        />
    </nav>
</template>
