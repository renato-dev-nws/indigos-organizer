<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoKpiCard from '@/Components/ui/BoKpiCard.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoStatusTag from '@/Components/ui/BoStatusTag.vue';
import BoPriorityTag from '@/Components/ui/BoPriorityTag.vue';
import BoDateText from '@/Components/ui/BoDateText.vue';

defineOptions({ layout: AppLayout });

defineProps({
    summary: Object,
    nextContents: Array,
    tasks: Array,
});
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader
            title="Dashboard"
            subtitle="Visão geral de ideias, conteúdos, tarefas e casas"
        />

        <div class="grid gap-4 lg:grid-cols-4">
            <BoKpiCard label="Ideias pendentes" :value="summary.pendingIdeas" icon="mdi:lightbulb-multiple-outline" />
            <BoKpiCard label="Conteúdos da semana" :value="summary.contentsThisWeek" icon="mdi:film-reel" />
            <BoKpiCard label="Tarefas urgentes" :value="summary.urgentOpenTasks" icon="ph:lightning-bold" />
            <BoKpiCard label="Casas de show" :value="summary.venuesCount" icon="ph:music-notes-bold" />
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            <!-- Próximos conteúdos -->
            <Card>
                <template #title>Próximos conteúdos</template>
                <template #content>
                    <DataTable :value="nextContents" data-key="id" striped-rows size="small" row-hover>
                        <Column field="title" header="Título">
                            <template #body="{ data }">
                                <Link :href="route('contents.show', data.id)" class="font-medium hover:text-indigo-600 hover:underline dark:hover:text-indigo-400">
                                    {{ data.title }}
                                </Link>
                            </template>
                        </Column>
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

            <!-- Tarefas (urgentes primeiro, depois por prazo) -->
            <Card>
                <template #title>Minhas tarefas</template>
                <template #content>
                    <DataTable :value="tasks" data-key="id" striped-rows size="small" row-hover>
                        <Column field="title" header="Título">
                            <template #body="{ data }">
                                <Link :href="route('tasks.edit', data.id)" class="font-medium hover:text-indigo-600 hover:underline dark:hover:text-indigo-400">
                                    {{ data.title }}
                                </Link>
                            </template>
                        </Column>
                        <Column header="Prioridade">
                            <template #body="{ data }">
                                <BoPriorityTag :value="data.priority" />
                            </template>
                        </Column>
                        <Column header="Prazo">
                            <template #body="{ data }">
                                <BoDateText :value="data.due_date" mode="date" />
                            </template>
                        </Column>
                        <Column header="Status">
                            <template #body="{ data }">
                                <span v-if="data.status" class="text-xs text-slate-500 dark:text-slate-400">{{ data.status?.name }}</span>
                                <span v-else class="text-xs text-slate-400">—</span>
                            </template>
                        </Column>
                        <template #empty>
                            <p class="py-6 text-center text-sm text-slate-500 dark:text-slate-400">Nenhuma tarefa atribuída.</p>
                        </template>
                    </DataTable>
                </template>
            </Card>
        </div>
    </div>
</template>
