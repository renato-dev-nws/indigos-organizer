<script setup>
defineProps({
    visible: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Detalhes',
    },
});

const emit = defineEmits(['update:visible']);
</script>

<template>
    <Drawer :visible="visible" position="right" class="w-full md:!w-[36rem]" @update:visible="emit('update:visible', $event)">
        <template #header>
            <h3 class="text-lg font-semibold">{{ title }}</h3>
        </template>
        <slot />
    </Drawer>
</template>
