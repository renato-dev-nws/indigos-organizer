<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import BoKpiCard from '@/Components/ui/BoKpiCard.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoStatusTag from '@/Components/ui/BoStatusTag.vue';

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
            subtitle="Visao geral de ideias, conteudos, tarefas e venues"
        />

        <div class="grid gap-4 lg:grid-cols-4">
            <BoKpiCard label="Ideias pendentes" :value="summary.pendingIdeas" icon="pi pi-lightbulb" />
            <BoKpiCard label="Conteudos da semana" :value="summary.contentsThisWeek" icon="pi pi-video" />
            <BoKpiCard label="Tarefas urgentes" :value="summary.urgentOpenTasks" icon="pi pi-bolt" />
            <BoKpiCard label="Casas de show" :value="summary.venuesCount" icon="pi pi-building" />
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            <Card>
                <template #title>Proximos conteudos</template>
                <template #content>
                    <DataTable :value="nextContents" data-key="id" striped-rows size="small">
                        <Column field="title" header="Titulo" />
                        <Column header="Status">
                            <template #body="{ data }">
                                <BoStatusTag :value="data.status" />
                            </template>
                        </Column>
                        <Column field="planned_publish_at" header="Publicacao" />
                        <template #empty>
                            <p class="py-6 text-center text-sm text-slate-500 dark:text-slate-400">Nenhum conteudo agendado.</p>
                        </template>
                    </DataTable>
                </template>
            </Card>

            <Card>
                <template #title>Tarefas urgentes</template>
                <template #content>
                    <DataTable :value="urgentTasks" data-key="id" striped-rows size="small">
                        <Column field="title" header="Titulo" />
                        <Column field="due_date" header="Prazo" />
                        <Column field="assignee" header="Responsavel" />
                        <template #empty>
                            <p class="py-6 text-center text-sm text-slate-500 dark:text-slate-400">Nenhuma tarefa urgente no momento.</p>
                        </template>
                    </DataTable>
                </template>
            </Card>
        </div>
    </div>
</template>
