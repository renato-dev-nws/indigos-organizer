<script setup>
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useTheme } from '@/Composables/useTheme';

const page = usePage();
const { setTheme } = useTheme();
const menu = ref(null);

const currentTheme = computed(() => page.props.auth?.user?.theme ?? 'system');

const items = computed(() => [
    {
        label: 'Tema',
        items: [
            {
                label: 'Claro',
                icon: currentTheme.value === 'light' ? 'pi pi-check' : 'pi pi-sun',
                command: () => setTheme('light'),
            },
            {
                label: 'Escuro',
                icon: currentTheme.value === 'dark' ? 'pi pi-check' : 'pi pi-moon',
                command: () => setTheme('dark'),
            },
            {
                label: 'Sistema',
                icon: currentTheme.value === 'system' ? 'pi pi-check' : 'pi pi-desktop',
                command: () => setTheme('system'),
            },
        ],
    },
]);

const toggle = (event) => menu.value?.toggle(event);
</script>

<template>
    <Button
        icon="pi pi-palette"
        rounded
        text
        aria-label="Selecionar tema"
        @click="toggle"
    />
    <Menu ref="menu" :model="items" popup />
</template>
