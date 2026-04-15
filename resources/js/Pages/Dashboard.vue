<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoPriorityTag from '@/Components/ui/BoPriorityTag.vue';
import BoStatusTag from '@/Components/ui/BoStatusTag.vue';
import BoDateText from '@/Components/ui/BoDateText.vue';

defineOptions({ layout: AppLayout });
const props = defineProps({
    summary: Object,
    boardIdeas: Array,
    nextScheduledTasks: Array,
    deadlineSoonTasks: Array,
    nextEvents: Array,
    contentsInProduction: Array,
    plansQueue: Array,
});
const page = usePage();

const vote = (ideaId, voteValue) => {
    router.post(route('ideas.vote', ideaId), { vote: voteValue }, { preserveScroll: true });
};

const dashboardCards = computed(() => [
    {
        key: 'tasks',
        title: 'Tarefas',
        value: props.summary?.tasksTotal || 0,
        subItems: [
            { label: 'Agendadas', value: props.summary?.tasksScheduled || 0 },
            { label: 'Suas tarefas', value: props.summary?.tasksMine || 0 },
            { label: 'Tarefas de todos', value: props.summary?.tasksEveryone || 0 },
            { label: 'Atrasadas', value: props.summary?.tasksOverdue || 0 },
        ],
    },
    {
        key: 'contents',
        title: 'Conteúdos',
        value: props.summary?.contentsTotal || 0,
        subItems: [
            { label: 'Na fila', value: props.summary?.contentsQueued || 0 },
            { label: 'Em produção', value: props.summary?.contentsInProduction || 0 },
            { label: 'Finalizados', value: props.summary?.contentsFinalized || 0 },
            { label: 'Publicados', value: props.summary?.contentsPublished || 0 },
        ],
    },
    {
        key: 'ideas',
        title: 'Ideias',
        value: props.summary?.ideasTotal || 0,
        subItems: [
            { label: 'Suas ideias', value: props.summary?.ideasMine || 0 },
            { label: 'Na gaveta', value: props.summary?.ideasInDrawer || 0 },
            { label: 'Na mesa', value: props.summary?.ideasOnTable || 0 },
            { label: 'No quadro', value: props.summary?.ideasOnBoard || 0 },
        ],
    },
]);

const generalCards = computed(() => [
    { title: 'Planejamentos em execução', value: props.summary?.plansRunning || 0, href: route('plans.index') },
    { title: 'Eventos', value: props.summary?.eventsActive || 0, href: route('events.index') },
    { title: 'Locais', value: props.summary?.venuesTotal || 0, href: route('venues.index') },
    { title: 'Contatos', value: props.summary?.contactsTotal || 0, href: route('contacts.index') },
]);

const mobileShortcuts = [
    { title: 'Tarefas', icon: 'pi pi-check-square', href: route('tasks.index') },
    { title: 'Conteúdos', icon: 'pi pi-video', href: route('contents.index') },
    { title: 'Ideias', icon: 'pi pi-lightbulb', href: route('ideas.index') },
    { title: 'Planejamentos', icon: 'pi pi-list-check', href: route('plans.index') },
    { title: 'Eventos', icon: 'pi pi-calendar-plus', href: route('events.index') },
    { title: 'Locais', icon: 'pi pi-map-marker', href: route('venues.index') },
    { title: 'Calendário', icon: 'pi pi-calendar', href: route('calendar.index') },
    { title: 'Informações úteis', icon: 'pi pi-info-circle', href: route('shared-infos.index') },
    { title: 'Contatos', icon: 'pi pi-address-book', href: route('contacts.index') },
];
</script>

<template>
    <div class="space-y-4 md:space-y-6">
        <div class="hidden md:block">
            <BoPageHeader title="Dashboard" subtitle="Resumo de como está sua oranização" icon="ph:squares-four-bold" />
        </div>

        <div class="block md:hidden">
            <BoPageHeader :title="`Olá, ${page.props.auth?.user?.name || ''}!`" subtitle="Organize sua arte aqui" icon="ph:sparkle-bold" />
        </div>

        <div class="hidden gap-3 md:grid md:grid-cols-3">
            <Card v-for="card in dashboardCards" :key="card.key" class="h-full">
                <template #content>
                    <p class="text-xs uppercase tracking-wide text-slate-500">{{ card.title }}</p>
                    <p class="mt-1 text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ card.value }}</p>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <div v-for="item in card.subItems" :key="item.label" class="rounded-lg bg-slate-100/80 p-2 text-xs dark:bg-slate-800/70">
                            <p class="text-slate-500 dark:text-slate-300">{{ item.label }}</p>
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ item.value }}</p>
                        </div>
                    </div>
                </template>
            </Card>
        </div>

        <div class="md:hidden">
            <Carousel :value="dashboardCards" :num-visible="1" :num-scroll="1" :show-indicators="true" :show-navigators="false" circular>
                <template #item="{ data }">
                    <Card>
                        <template #content>
                            <p class="text-xs uppercase tracking-wide text-slate-500">{{ data.title }}</p>
                            <p class="mt-1 text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ data.value }}</p>
                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <div v-for="item in data.subItems" :key="item.label" class="rounded-lg bg-slate-100/80 p-2 text-xs dark:bg-slate-800/70">
                                    <p class="text-slate-500 dark:text-slate-300">{{ item.label }}</p>
                                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ item.value }}</p>
                                </div>
                            </div>
                        </template>
                    </Card>
                </template>
            </Carousel>
        </div>

        <div class="grid grid-cols-3 gap-2 md:hidden">
            <Link v-for="shortcut in mobileShortcuts" :key="shortcut.title" :href="shortcut.href" class="rounded-xl border border-slate-200 bg-white px-2 py-3 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <i :class="shortcut.icon" class="mb-1 text-base text-slate-600 dark:text-slate-200" />
                <p class="text-[11px] font-medium leading-tight">{{ shortcut.title }}</p>
            </Link>
        </div>

        <div class="grid grid-cols-2 gap-3 md:grid-cols-2 xl:grid-cols-4">
            <Link v-for="card in generalCards" :key="card.title" :href="card.href">
                <Card class="h-full transition hover:-translate-y-0.5 hover:shadow-md">
                    <template #content>
                        <p class="text-xs uppercase tracking-wide text-slate-500">{{ card.title }}</p>
                        <p class="mt-1 text-3xl font-semibold text-slate-900 dark:text-slate-100">{{ card.value }}</p>
                    </template>
                </Card>
            </Link>
        </div>

        <div class="hidden gap-4 xl:grid xl:grid-cols-2">
            <Card>
                <template #content>
                    <h3 class="mb-3 text-sm font-semibold">Próximas tarefas agendadas</h3>
                    <DataTable :value="nextScheduledTasks" data-key="id" size="small" striped-rows>
                        <Column header="Título">
                            <template #body="{ data }">
                                <Link :href="route('tasks.edit', data.id)" class="font-medium hover:underline">{{ data.title }}</Link>
                            </template>
                        </Column>
                        <Column header="Responsável">
                            <template #body="{ data }">{{ data.assigned_user?.name || data.assignedUser?.name || 'Todos' }}</template>
                        </Column>
                        <Column header="Agendado"><template #body="{ data }"><BoDateText :value="data.scheduled_for" mode="datetime" /></template></Column>
                    </DataTable>
                </template>
            </Card>

            <Card>
                <template #content>
                    <h3 class="mb-3 text-sm font-semibold">Tarefas próximas de deadline</h3>
                    <DataTable :value="deadlineSoonTasks" data-key="id" size="small" striped-rows>
                        <Column header="Título">
                            <template #body="{ data }">
                                <Link :href="route('tasks.edit', data.id)" class="font-medium hover:underline">{{ data.title }}</Link>
                            </template>
                        </Column>
                        <Column header="Prioridade">
                            <template #body="{ data }"><BoPriorityTag :value="data.priority" /></template>
                        </Column>
                        <Column header="Prazo"><template #body="{ data }"><BoDateText :value="data.due_date" mode="date" /></template></Column>
                    </DataTable>
                </template>
            </Card>

            <Card>
                <template #content>
                    <h3 class="mb-3 text-sm font-semibold">Próximos eventos</h3>
                    <DataTable :value="nextEvents" data-key="id" size="small" striped-rows>
                        <Column header="Título">
                            <template #body="{ data }">
                                <Link :href="route('events.show', data.id)" class="font-medium hover:underline">{{ data.title }}</Link>
                            </template>
                        </Column>
                        <Column header="Tipo"><template #body="{ data }">{{ data.type?.name || '-' }}</template></Column>
                        <Column header="Data"><template #body="{ data }"><BoDateText :value="data.starts_at" mode="datetime" /></template></Column>
                    </DataTable>
                </template>
            </Card>

            <Card>
                <template #content>
                    <h3 class="mb-3 text-sm font-semibold">Conteúdos em produção</h3>
                    <DataTable :value="contentsInProduction" data-key="id" size="small" striped-rows>
                        <Column header="Título">
                            <template #body="{ data }">
                                <Link :href="route('contents.show', data.id)" class="font-medium hover:underline">{{ data.title }}</Link>
                            </template>
                        </Column>
                        <Column header="Status"><template #body="{ data }"><BoStatusTag :value="data.status" /></template></Column>
                        <Column header="Publicação"><template #body="{ data }"><BoDateText :value="data.planned_publish_at" mode="datetime" /></template></Column>
                    </DataTable>
                </template>
            </Card>

            <Card>
                <template #content>
                    <h3 class="mb-3 text-sm font-semibold">Planejamentos</h3>
                    <DataTable :value="plansQueue" data-key="id" size="small" striped-rows>
                        <Column header="Título">
                            <template #body="{ data }">
                                <Link :href="route('plans.show', data.id)" class="font-medium hover:underline">{{ data.title }}</Link>
                            </template>
                        </Column>
                        <Column header="Status"><template #body="{ data }"><BoStatusTag :value="data.status" /></template></Column>
                        <Column header="Atualização"><template #body="{ data }"><BoDateText :value="data.updated_at" mode="datetime" /></template></Column>
                    </DataTable>
                </template>
            </Card>

            <Card>
                <template #content>
                    <h3 class="mb-3 text-sm font-semibold">Votação</h3>
                    <div v-if="boardIdeas.length" class="space-y-3">
                        <div v-for="idea in boardIdeas" :key="idea.id" class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-900">
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
        </div>

        <Card class="xl:hidden">
            <template #title>Votação</template>
            <template #content>
                <div v-if="boardIdeas.length" class="space-y-3">
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
    </div>
</template>
