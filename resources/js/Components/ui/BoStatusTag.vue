<script setup>
import { computed } from 'vue';

const props = defineProps({
    value: {
        type: String,
        default: '',
    },
});

const styles = {
    pending: { label: 'Pendente', color: '#94a3b8' },
    maturing: { label: 'Maturando', color: '#f59e0b' },
    cancelled: { label: 'Cancelado', color: '#ef4444' },
    in_production: { label: 'Em producao', color: '#3b82f6' },
    executed: { label: 'Executado', color: '#10b981' },
    queued: { label: 'Na fila', color: '#94a3b8' },
    paused: { label: 'Pausado', color: '#f59e0b' },
    published: { label: 'Publicado', color: '#10b981' },
};

const mapped = computed(() => styles[props.value] ?? { label: props.value || 'Sem status', color: '#64748b' });
</script>

<template>
    <Tag :value="mapped.label" :style="{ backgroundColor: mapped.color, color: 'white' }" rounded />
</template>
