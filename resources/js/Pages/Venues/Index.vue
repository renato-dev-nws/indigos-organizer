<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppPagination from '@/Components/AppPagination.vue';

defineOptions({ layout: AppLayout });
const props = defineProps({ venues: Object });
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Casas de Show</h1>
            <Link :href="route('venues.create')" class="rounded bg-slate-900 px-3 py-2 text-white dark:bg-slate-200 dark:text-slate-900">Nova casa</Link>
        </div>
        <div v-for="venue in props.venues.data" :key="venue.id" class="rounded border bg-white p-4 dark:bg-slate-800">
            <p class="font-semibold">{{ venue.name }}</p>
            <p class="text-sm text-slate-500">{{ venue.contact_name }}</p>
        </div>
        <AppPagination :links="props.venues.links" />
    </div>
</template>
