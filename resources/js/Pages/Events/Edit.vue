<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import EventForm from '@/Components/events/EventForm.vue';

const props = defineProps({
    event: Object,
    types: Array,
    venues: Array,
    venueTypes: Array,
    venueCategories: Array,
    venueStyles: Array,
});

defineOptions({ layout: AppLayout });

const form = useForm({
    title: props.event.title,
    event_type_id: props.event.event_type_id,
    venue_id: props.event.venue_id,
    attendance_mode: props.event.attendance_mode,
    is_online: !!props.event.is_online,
    description: props.event.description,
    event_date: props.event.event_date,
    event_time: props.event.event_time ? String(props.event.event_time).slice(0, 5) : '',
    end_date: props.event.end_date,
    end_time: props.event.end_time ? String(props.event.end_time).slice(0, 5) : '',
    ticket_link: props.event.ticket_link,
    ticket_price_first_batch: props.event.ticket_price_first_batch,
    ticket_price_second_batch: props.event.ticket_price_second_batch,
    ticket_price_third_batch: props.event.ticket_price_third_batch,
    ticket_price_door: props.event.ticket_price_door,
    extra_infos: props.event.extra_infos?.map((item) => ({ title: item.title, information: item.information })) || [],
    links: props.event.links?.map((item) => ({ title: item.title, url: item.url })) || [],
});

const submit = () => form.put(route('events.update', props.event.id));
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Editar evento" supratitle="EVENTOS" subtitle="" icon="mdi:circle-edit-outline">
            <template #actions>
                <Link :href="route('events.show', event.id)">
                    <Button class="!hidden md:!inline-flex" label="Visualizar" outlined severity="secondary" icon="pi pi-eye" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-eye" rounded outlined severity="secondary" aria-label="Visualizar" />
                </Link>
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
                    :cancel-href="route('events.show', event.id)"
                    submit-label="Atualizar evento"
                    @submit="submit"
                />
            </template>
        </Card>
    </div>
</template>