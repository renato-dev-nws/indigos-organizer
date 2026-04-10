<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ venue: Object });
defineOptions({ layout: AppLayout });

const form = useForm({ ...props.venue });

const submit = () => form.put(route('venues.update', props.venue.id));
</script>

<template>
    <div class="space-y-4">
        <h1 class="text-2xl font-bold">Editar casa</h1>
        <form class="space-y-3" @submit.prevent="submit">
            <input v-model="form.name" class="w-full rounded border p-2" />
            <button class="rounded bg-slate-900 px-3 py-2 text-white" type="submit">Atualizar</button>
        </form>
    </div>
</template>
