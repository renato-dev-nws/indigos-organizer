<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoPriorityTag from '@/Components/ui/BoPriorityTag.vue';

defineOptions({ layout: AppLayout });
const props = defineProps({ boardIdeas: Array, myTasks: Array });

const vote = (ideaId, voteValue) => {
    router.post(route('ideas.vote', ideaId), { vote: voteValue }, { preserveScroll: true });
};

const dashboardCards = computed(() => [
    {
        title: 'Ideias aguardando voto',
        value: props.boardIdeas.length,
        subtitle: 'No quadro para você decidir',
    },
    {
        title: 'Minhas tarefas recentes',
        value: props.myTasks.length,
        subtitle: 'Últimas 5 tarefas visíveis',
    },
    {
        title: 'Prioridade urgente',
        value: props.myTasks.filter((task) => task.priority === 'urgent').length,
        subtitle: 'Itens críticos na sua fila',
    },
]);
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Dashboard" subtitle="Pendências de votação e tarefas recentes" />

        <div class="grid gap-3 md:grid-cols-3">
            <Card v-for="card in dashboardCards" :key="card.title">
                <template #content>
                    <p class="text-xs uppercase tracking-wide text-slate-500">{{ card.title }}</p>
                    <p class="mt-1 text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ card.value }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ card.subtitle }}</p>
                </template>
            </Card>
        </div>

        <Card>
            <template #title>Ideias para votação</template>
            <template #content>
                <div v-if="boardIdeas.length" class="grid gap-3 md:grid-cols-2">
                    <div v-for="idea in boardIdeas" :key="idea.id" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="mb-2 flex items-start justify-between gap-2">
                            <Link :href="route('ideas.show', idea.id)" class="font-semibold hover:underline">{{ idea.title }}</Link>
                            <Tag value="No quadro" severity="warn" />
                        </div>
                        <p class="mb-3 text-sm text-slate-500">Criada por {{ idea.user?.name || '-' }}</p>
                        <div class="flex flex-wrap gap-2">
                            <Button size="small" label="Na mesa" @click="vote(idea.id, 'on_table')" />
                            <Button size="small" label="Na gaveta" severity="secondary" @click="vote(idea.id, 'in_drawer')" />
                            <Button size="small" label="No lixo" severity="danger" @click="vote(idea.id, 'trash')" />
                        </div>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-500">Nenhuma ideia pendente de voto para você.</p>
            </template>
        </Card>

        <Card>
            <template #title>Minhas tarefas recentes</template>
            <template #content>
                <DataTable :value="myTasks" data-key="id" striped-rows size="small">
                    <Column field="title" header="Título">
                        <template #body="{ data }">
                            <Link :href="route('tasks.edit', data.id)" class="font-medium hover:underline">{{ data.title }}</Link>
                        </template>
                    </Column>
                    <Column header="Prioridade">
                        <template #body="{ data }">
                            <BoPriorityTag :value="data.priority" />
                        </template>
                    </Column>
                    <Column header="Status">
                        <template #body="{ data }">{{ data.status?.name || '-' }}</template>
                    </Column>
                </DataTable>
            </template>
        </Card>
    </div>
</template>
