<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <Card>
        <template #title>{{ title }}</template>
        <template #subtitle>
            <span v-if="description">{{ description }}</span>
        </template>
        <template #content>
            <div class="grid gap-4 md:grid-cols-2">
                <slot />
            </div>
        </template>
    </Card>
</template>
