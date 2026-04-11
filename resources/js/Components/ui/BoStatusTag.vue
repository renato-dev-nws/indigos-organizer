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
    executed: { label: 'Executada', color: '#10b981' },
    in_drawer: { label: 'Na gaveta', color: '#64748b' },
    on_table: { label: 'Na mesa', color: '#2563eb' },
    on_board: { label: 'No quadro', color: '#7c3aed' },
    executing: { label: 'Em execução', color: '#4f46e5' },
    trash: { label: 'No lixo', color: '#dc2626' },
    queued: { label: 'Na fila', color: '#94a3b8' },
    running: { label: 'Em execução', color: '#2563eb' },
    paused: { label: 'Pausado', color: '#f59e0b' },
    published: { label: 'Publicado', color: '#10b981' },
    completed: { label: 'Concluído', color: '#10b981' },
};

const mapped = computed(() => styles[props.value] ?? { label: props.value || 'Sem status', color: '#64748b' });
</script>

<template>
    <Tag :value="mapped.label" :style="{ backgroundColor: mapped.color, color: 'white' }" rounded />
</template>
