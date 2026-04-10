<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });
defineProps({ venue: Object });
</script>

<template>
    <div class="space-y-4">
        <h1 class="text-2xl font-bold">{{ venue.name }}</h1>
        <p>{{ venue.description }}</p>
        <p>{{ venue.notes }}</p>
    </div>
</template>
