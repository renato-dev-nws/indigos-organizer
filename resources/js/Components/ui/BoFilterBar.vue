<script setup>
import { ref } from 'vue';

const props = defineProps({
    chips: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['submit', 'reset', 'remove-chip']);

const visible = ref(false);

const applyAndClose = () => {
    emit('submit');
    visible.value = false;
};

const resetAndClose = () => {
    emit('reset');
    visible.value = false;
};
</script>

<template>
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <Button
            :icon="chips.length ? 'pi pi-filter-fill' : 'pi pi-filter'"
            :label="chips.length ? `Filtros (${chips.length})` : 'Filtros'"
            :severity="chips.length ? undefined : 'secondary'"
            :outlined="!chips.length"
            size="small"
            @click="visible = true"
        />

        <Chip
            v-for="chip in chips"
            :key="chip.key"
            :label="chip.label"
            removable
            class="text-sm"
            @remove="emit('remove-chip', chip.key)"
        />

        <Button
            v-if="chips.length"
            label="Limpar filtros"
            icon="pi pi-times"
            size="small"
            text
            severity="secondary"
            @click="resetAndClose"
        />
    </div>

    <Dialog
        v-model:visible="visible"
        header="Filtros"
        modal
        :draggable="false"
        :style="{ width: '480px' }"
        :breakpoints="{ '640px': '95vw' }"
    >
        <form class="space-y-4 pt-1" @submit.prevent="applyAndClose">
            <slot />
        </form>
        <template #footer>
            <Button label="Cancelar" text severity="secondary" @click="visible = false" />
            <Button label="Aplicar filtros" icon="pi pi-check" @click="applyAndClose" />
        </template>
    </Dialog>
</template>
