<script setup>
const emit = defineEmits(['submit', 'reset']);
</script>

<template>
    <Card>
        <template #content>
            <form class="grid gap-3 md:grid-cols-4" @submit.prevent="emit('submit')">
                <slot />
                <div class="md:col-span-4 flex items-center gap-2">
                    <Button type="submit" icon="pi pi-filter" label="Aplicar filtros" />
                    <Button type="button" outlined severity="secondary" icon="pi pi-times" label="Limpar" @click="emit('reset')" />
                </div>
            </form>
        </template>
    </Card>
</template>
