<script setup>
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({
    label: {
        type: String,
        default: 'Confirmar',
    },
    icon: {
        type: String,
        default: 'pi pi-check',
    },
    severity: {
        type: String,
        default: 'danger',
    },
    message: {
        type: String,
        default: 'Deseja continuar com esta acao?',
    },
    header: {
        type: String,
        default: 'Confirmacao',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['confirm']);
const confirm = useConfirm();

const onClick = () => {
    if (props.disabled) {
        return;
    }

    confirm.require({
        message: props.message,
        header: props.header,
        icon: 'pi pi-exclamation-triangle',
        rejectProps: {
            label: 'Cancelar',
            severity: 'secondary',
            outlined: true,
        },
        acceptProps: {
            label: 'Confirmar',
            severity: props.severity,
        },
        accept: () => emit('confirm'),
    });
};
</script>

<template>
    <Button :label="label" :icon="icon" :severity="severity" :disabled="disabled" outlined @click="onClick" />
</template>
