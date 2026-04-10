<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useTheme } from '@/Composables/useTheme';

const page = usePage();
const { setTheme } = useTheme();

const currentTheme = computed(() => page.props.auth?.user?.theme ?? 'system');
const options = [
    { label: 'Claro', value: 'light' },
    { label: 'Escuro', value: 'dark' },
    { label: 'Sistema', value: 'system' },
];
</script>

<template>
    <SelectButton
        :model-value="currentTheme"
        :options="options"
        option-label="label"
        option-value="value"
        size="small"
        @update:model-value="setTheme"
    />
</template>
