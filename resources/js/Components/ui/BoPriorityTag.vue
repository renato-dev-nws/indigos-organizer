<script setup>
import { computed } from 'vue';

const props = defineProps({
    value: {
        type: String,
        default: '',
    },
});

const map = {
    low: { label: 'Baixa', color: '#94a3b8' },
    medium: { label: 'Media', color: '#3b82f6' },
    high: { label: 'Alta', color: '#f59e0b' },
    urgent: { label: 'Urgente', color: '#ef4444' },
};

const mapped = computed(() => map[props.value] ?? { label: props.value || 'N/A', color: '#64748b' });
</script>

<template>
    <Tag :value="mapped.label" :style="{ backgroundColor: mapped.color, color: 'white' }" />
</template>
