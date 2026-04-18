<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import EventForm from '@/Components/events/EventForm.vue';

defineOptions({ layout: AppLayout });

const goBack = () => {
    if (typeof window !== 'undefined' && window.history.length > 1) {
        window.history.back();
    }
};

defineProps({
    types: Array,
    venues: Array,
    venueTypes: Array,
    venueCategories: Array,
    venueStyles: Array,
});

const form = useForm({
    title: '',
    event_type_id: null,
    venue_id: null,
    attendance_mode: 'participant',
    is_online: false,
    description: '',
    event_date: '',
    event_time: '',
    end_date: '',
    end_time: '',
    ticket_link: '',
    ticket_price_first_batch: null,
    ticket_price_second_batch: null,
    ticket_price_third_batch: null,
    ticket_price_door: null,
    extra_infos: [],
    links: [],
});

const submit = () => form.post(route('events.store'));
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Novo evento" supratitle="EVENTOS" subtitle="" icon="mdi:add-circle-outline">
            <template #actions>
                <div>
                    <Button class="!hidden md:!inline-flex" label="Voltar" outlined severity="secondary" icon="pi pi-arrow-left" @click="goBack" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-arrow-left" rounded outlined severity="secondary" aria-label="Voltar" @click="goBack" />
                </div>
            </template>
        </BoPageHeader>

        <Card>
            <template #content>
                <EventForm
                    :form="form"
                    :types="types"
                    :venues="venues"
                    :venue-types="venueTypes"
                    :venue-categories="venueCategories"
                    :venue-styles="venueStyles"
                    cancel-href="/events"
                    submit-label="Salvar evento"
                    @submit="submit"
                />
            </template>
        </Card>
    </div>
</template>