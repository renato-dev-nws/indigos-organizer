<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import timeGridPlugin from '@fullcalendar/timegrid';
import ptBrLocale from '@fullcalendar/core/locales/pt-br';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import TaskViewModal from '@/Components/tasks/TaskViewModal.vue';

defineOptions({ layout: AppLayout });

defineProps({
    calendarItems: Array,
    legend: Array,
});

const selectedTask = ref(null);
const showTaskViewModal = ref(false);

const openTaskModalById = async (taskId) => {
    if (!taskId) {
        return;
    }

    const response = await fetch(route('tasks.show', taskId), {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });
    const payload = await response.json();

    if (payload?.task) {
        selectedTask.value = payload.task;
        showTaskViewModal.value = true;
    }
};

const calendarOptions = (items) => ({
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    firstDay: 0,
    locale: 'pt-br',
    locales: [ptBrLocale],
    height: 'auto',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek',
    },
    buttonText: {
        today: 'Hoje',
        month: 'Mês',
        week: 'Semana',
    },
    events: (items || []).map((item) => ({
        ...item,
        display: item.display || 'block',
        backgroundColor: item.color,
        borderColor: item.color,
        textColor: '#ffffff',
    })),
    eventClick: async (info) => {
        const taskId = info.event.extendedProps?.task_id;
        if (taskId) {
            info.jsEvent.preventDefault();
            await openTaskModalById(taskId);
            return;
        }

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
                    <div
                        v-for="item in legend"
                        :key="item.label"
                        class="rounded-full px-3 py-1 text-xs font-medium text-white"
                        :style="{ backgroundColor: item.color }"
                    >
                        {{ item.label }}
                    </div>
                </div>

                <FullCalendar :options="calendarOptions(calendarItems || [])" />
            </template>
        </Card>

        <TaskViewModal v-model:visible="showTaskViewModal" :task="selectedTask" />
    </div>
</template>