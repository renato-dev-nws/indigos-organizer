<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: [Object, String, null],
        default: null,
    },
});

const normalize = (value) => String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim();

const defaults = {
    pending: { label: 'Pendente', color: '#dc2626' },
    running: { label: 'Em execução', color: '#2563eb' },
    review: { label: 'Revisão', color: '#eab308' },
    done: { label: 'Concluída', color: '#16a34a' },
};

const mapped = computed(() => {
    const statusObj = typeof props.status === 'object' ? props.status : null;
    const label = statusObj?.name || (typeof props.status === 'string' ? props.status : 'Sem status');
    const normalized = normalize(label);

    if (normalized.includes('pend')) {
        return { label, color: statusObj?.color || defaults.pending.color };
    }
    if (normalized.includes('exec') || normalized.includes('running')) {
        return { label, color: statusObj?.color || defaults.running.color };
    }
    if (normalized.includes('revis')) {
        return { label, color: statusObj?.color || defaults.review.color };
    }
    if (normalized.includes('concl') || normalized.includes('finaliz') || normalized.includes('done') || normalized.includes('complete')) {
        return { label, color: statusObj?.color || defaults.done.color };
    }

    return { label, color: statusObj?.color || '#64748b' };
});
</script>

<template>
    <Tag :value="mapped.label" :style="{ backgroundColor: mapped.color, color: 'white' }" rounded />
</template>
