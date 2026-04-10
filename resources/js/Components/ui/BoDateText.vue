<script setup>
import { computed } from 'vue';
import { formatDateBR, formatDateTimeBR } from '@/Utils/formatters';

const props = defineProps({
    value: {
        type: [String, Date, null],
        default: null,
    },
    mode: {
        type: String,
        default: 'datetime',
    },
    emptyLabel: {
        type: String,
        default: '-',
    },
});

const formatted = computed(() => {
    if (!props.value) {
        return props.emptyLabel;
    }

    return props.mode === 'date' ? formatDateBR(props.value) : formatDateTimeBR(props.value);
});
</script>

<template>
    <span>{{ formatted }}</span>
</template>
