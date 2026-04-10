<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import BoKpiCard from '@/Components/ui/BoKpiCard.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoStatusTag from '@/Components/ui/BoStatusTag.vue';
import BoDateText from '@/Components/ui/BoDateText.vue';

defineOptions({ layout: AppLayout });

defineProps({
    summary: Object,
    nextContents: Array,
    urgentTasks: Array,
});
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader
            title="Dashboard"
            subtitle="Visão geral de ideias, conteúdos, tarefas e casas"
        />

        <div class="grid gap-4 lg:grid-cols-4">
            <BoKpiCard label="Ideias pendentes" :value="summary.pendingIdeas" icon="ph:lightbulb-bold" />
            <BoKpiCard label="Conteúdos da semana" :value="summary.contentsThisWeek" icon="ph:video-camera-bold" />
            <BoKpiCard label="Tarefas urgentes" :value="summary.urgentOpenTasks" icon="ph:lightning-bold" />
            <BoKpiCard label="Casas de show" :value="summary.venuesCount" icon="ph:music-notes-bold" />
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            <Card>
                <template #title>Próximos conteúdos</template>
                <template #content>
                    <DataTable :value="nextContents" data-key="id" striped-rows size="small">
                        <Column field="title" header="Título" />
                        <Column header="Autor">
                            <template #body="{ data }">{{ data.user?.name || '-' }}</template>
                        </Column>
                        <Column header="Status">
                            <template #body="{ data }">
                                <BoStatusTag :value="data.status" />
                            </template>
                        </Column>
                        <Column header="Publicação">
                            <template #body="{ data }">
                                <BoDateText :value="data.planned_publish_at" mode="datetime" />
                            </template>
                        </Column>
                        <template #empty>
                            <p class="py-6 text-center text-sm text-slate-500 dark:text-slate-400">Nenhum conteúdo agendado.</p>
                        </template>
                    </DataTable>
                </template>
            </Card>

            <Card>
                <template #title>Tarefas urgentes</template>
                <template #content>
                    <DataTable :value="urgentTasks" data-key="id" striped-rows size="small">
                        <Column field="title" header="Título" />
                        <Column header="Autor">
                            <template #body="{ data }">{{ data.user?.name || '-' }}</template>
                        </Column>
                        <Column header="Prazo">
                            <template #body="{ data }">
                                <BoDateText :value="data.due_date" mode="date" />
                            </template>
                        </Column>
                        <Column field="assignee" header="Responsável" />
                        <template #empty>
                            <p class="py-6 text-center text-sm text-slate-500 dark:text-slate-400">Nenhuma tarefa urgente no momento.</p>
                        </template>
                    </DataTable>
                </template>
            </Card>
        </div>
    </div>
</template>
