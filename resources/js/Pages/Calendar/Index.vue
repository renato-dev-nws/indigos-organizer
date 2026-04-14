<script setup>
import { router } from '@inertiajs/vue3';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';

defineOptions({ layout: AppLayout });

defineProps({
    calendarItems: Array,
    legend: Array,
});

const calendarOptions = (items) => ({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    firstDay: 0,
    locale: 'pt-br',
    height: 'auto',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek',
    },
    events: items,
    eventClick: (info) => {
        const url = info.event.extendedProps?.url;
        if (!url) {
            return;
        }

        info.jsEvent.preventDefault();
        router.visit(url);
    },
});
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Calendário geral" subtitle="Agenda unificada de conteúdos e eventos" />

        <Card>
            <template #content>
                <div class="mb-4 flex flex-wrap gap-3">
                    <div v-for="item in legend" :key="item.label" class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                        <span class="h-3 w-3 rounded-full" :style="{ backgroundColor: item.color }" />
                        <span>{{ item.label }}</span>
                    </div>
                </div>

                <FullCalendar :options="calendarOptions(calendarItems || [])" />
            </template>
        </Card>
    </div>
</template>