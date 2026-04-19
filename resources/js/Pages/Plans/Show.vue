<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoStatusTag from '@/Components/ui/BoStatusTag.vue';
import TaskViewModal from '@/Components/tasks/TaskViewModal.vue';

defineOptions({ layout: AppLayout });

const goBack = () => {
    if (typeof window !== 'undefined' && window.history.length > 1) {
        window.history.back();
    }
};
const props = defineProps({ plan: Object });
const confirm = useConfirm();

const activeTask = ref(null);
const taskModalVisible = ref(false);
const standaloneTasks = computed(() => (props.plan.tasks || []).filter((task) => !task.plan_phase_id));
const phaseTasksTotal = computed(() => (props.plan.phases || []).reduce((total, phase) => total + (phase.tasks?.length || 0), 0));
const relatedTasksTotal = computed(() => phaseTasksTotal.value + standaloneTasks.value.length);

const contentStatusLabels = {
    queued: 'Na fila',
    in_production: 'Em produção',
    finalized: 'Finalizado',
    cancelled: 'Cancelado',
    paused: 'Pausado',
    published: 'Publicado',
};

const openTask = (task) => {
    activeTask.value = task;
    taskModalVisible.value = true;
};

const assigneeLabel = (task) => {
    const assignees = task?.assigned_users || task?.assignedUsers || [];
    if (!assignees.length) {
        return 'Todos';
    }

    return assignees.map((user) => user.name).join(', ');
};

const isCompletedTaskStatus = (name) => {
    const normalized = String(name || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase();

    return ['conclu', 'finaliz', 'done', 'completed'].some((item) => normalized.includes(item));
};

const taskScheduleLabel = (task) => {
    if (task?.scheduled_for) {
        return `Agendada para: ${new Date(task.scheduled_for).toLocaleString('pt-BR')}`;
    }

    if (task?.due_date) {
        return `Prazo: ${new Date(task.due_date).toLocaleDateString('pt-BR')}`;
    }

    return 'Prazo: não definido';
};

const contentStatusLabel = (status) => contentStatusLabels[status] || status || 'Sem status';

const requiresPreviousPhaseConfirmation = (phase) => {
    const phases = props.plan.phases || [];
    const currentIndex = phases.findIndex((item) => item.id === phase.id);
    if (currentIndex <= 0) {
        return false;
    }

    return phases.slice(0, currentIndex).some((item) => !item.completed);
};

const requiresPendingTasksConfirmation = (phase) => {
    return (phase.tasks || []).some((task) => !isCompletedTaskStatus(task.status?.name));
};

const togglePhaseCompletion = (phase, completed) => {
    const shouldComplete = !!completed;

    const applyCompletion = () => {
        router.patch(
            route('plans.phases.completion', [props.plan.id, phase.id]),
            { completed: shouldComplete },
            { preserveScroll: true },
        );
    };

    if (!shouldComplete || phase.completed) {
        applyCompletion();
        return;
    }

    const confirmations = [];

    if (requiresPreviousPhaseConfirmation(phase)) {
        confirmations.push('Fase(s) anterior(es) não concluída(s) ainda! Tem certeza que deseja marcar esta fase como concluída?');
    }

    if (requiresPendingTasksConfirmation(phase)) {
        confirmations.push('Esta fase tem tarefas não concluídas! Tem certeza que deseja marcar a fase como concluída?');
    }

    if (!confirmations.length) {
        applyCompletion();
        return;
    }

    const runConfirmation = (index) => {
        if (index >= confirmations.length) {
            applyCompletion();
            return;
        }

        confirm.require({
            header: 'Confirmar conclusão da fase',
            message: confirmations[index],
            icon: 'pi pi-exclamation-triangle',
            rejectLabel: 'Cancelar',
            acceptLabel: 'Confirmar',
            acceptClass: 'p-button-sm',
            rejectClass: 'p-button-text p-button-sm',
            accept: () => runConfirmation(index + 1),
        });
    };

    runConfirmation(0);
};
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader :title="plan.title" subtitle="Detalhes do plano">
            <template #actions>
                <div>
                    <Button type="button" class="!hidden md:!inline-flex" icon="pi pi-arrow-left" label="Voltar" outlined severity="secondary" @click="goBack" />
                    <Button type="button" class="!inline-flex md:!hidden" icon="pi pi-arrow-left" rounded outlined severity="secondary" aria-label="Voltar" @click="goBack" />
                </div>
                <Link :href="route('plans.edit', plan.id)">
                    <Button type="button" class="!hidden md:!inline-flex" icon="pi pi-pencil" label="Editar" />
                    <Button type="button" class="!inline-flex md:!hidden" icon="pi pi-pencil" rounded aria-label="Editar" />
                </Link>
            </template>
        </BoPageHeader>

        <Card>
            <template #content>
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-500">Status</p>
                        <BoStatusTag :value="plan.status" />
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-500">Progresso</p>
                        <ProgressBar :value="plan.progress" style="height:0.6rem" />
                        <p class="mt-1 text-xs text-slate-500">{{ plan.progress || 0 }}%</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-500">Volume</p>
                        <div class="mt-1 flex flex-wrap gap-2 text-xs">
                            <Tag :value="`${plan.phases?.length || 0} fases`" severity="info" />
                            <Tag :value="`${relatedTasksTotal} tarefas relacionadas`" severity="secondary" />
                            <Tag :value="`${(plan.contents || []).length} conteúdos relacionados`" severity="success" />
                        </div>
                    </div>
                </div>
            </template>
        </Card>

        <Card>
            <template #title>Fases e tarefas</template>
            <template #content>
                <p class="text-sm text-slate-600 dark:text-slate-300">{{ plan.description || 'Sem descrição.' }}</p>
                <hr class="my-4 border-slate-200 dark:border-slate-700" />

                <div class="mb-4">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Tarefas da fase</h3>
                </div>

                <div class="space-y-4">
                    <div v-for="phase in plan.phases" :key="phase.id" class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <h3 class="font-semibold">{{ phase.order }}. {{ phase.title }}</h3>
                            <div class="flex items-center gap-2">
                                <Tag :value="`${phase.tasks?.length || 0} tarefas`" severity="secondary" />
                                <div class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-1.5 dark:border-slate-700">
                                    <Checkbox :model-value="!!phase.completed" binary :input-id="`phase-completed-view-${phase.id}`" @update:model-value="(value) => togglePhaseCompletion(phase, value)" />
                                    <label :for="`phase-completed-view-${phase.id}`" class="text-xs font-medium">Concluída</label>
                                </div>
                            </div>
                        </div>
                        <p class="mb-2 text-sm text-slate-500">{{ phase.description || 'Sem descrição' }}</p>
                        <div class="mb-3 flex flex-wrap gap-2">
                            <Tag v-if="phase.estimated_start_date" severity="info" :value="`Previsão início: ${new Date(phase.estimated_start_date).toLocaleDateString('pt-BR')}`" />
                            <Tag v-if="phase.estimated_end_date" severity="warning" :value="`Previsão final: ${new Date(phase.estimated_end_date).toLocaleDateString('pt-BR')}`" />
                        </div>

                        <div class="grid gap-2 md:grid-cols-2">
                            <button
                                v-for="task in phase.tasks"
                                :key="task.id"
                                type="button"
                                class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-left transition hover:border-indigo-300 hover:bg-indigo-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-indigo-700 dark:hover:bg-slate-800"
                                @click="openTask(task)"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold">{{ task.title }}</p>
                                        <p class="text-xs text-slate-500">{{ assigneeLabel(task) }}</p>
                                    </div>
                                    <div class="text-right">
                                        <BoStatusTag :value="task.status?.name || 'Sem status'" />
                                        <p class="mt-1 text-xs text-slate-500">{{ taskScheduleLabel(task) }}</p>
                                    </div>
                                </div>
                            </button>
                            <p v-if="!phase.tasks.length" class="text-sm text-slate-500">Nenhuma tarefa nesta fase.</p>
                        </div>
                    </div>
                </div>
            </template>
        </Card>

        <Card>
            <template #title>Tarefas diretamente no plano</template>
            <template #content>
                <div class="grid gap-2 md:grid-cols-2">
                    <button
                        v-for="task in standaloneTasks"
                        :key="task.id"
                        type="button"
                        class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-left transition hover:border-indigo-300 hover:bg-indigo-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-indigo-700 dark:hover:bg-slate-800"
                        @click="openTask(task)"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold">{{ task.title }}</p>
                                <p class="text-xs text-slate-500">{{ assigneeLabel(task) }}</p>
                            </div>
                            <div class="text-right">
                                <BoStatusTag :value="task.status?.name || 'Sem status'" />
                                <p class="mt-1 text-xs text-slate-500">{{ taskScheduleLabel(task) }}</p>
                            </div>
                        </div>
                    </button>
                    <p v-if="!standaloneTasks.length" class="text-sm text-slate-500">Nenhuma tarefa vinculada diretamente.</p>
                </div>
            </template>
        </Card>

        <Card>
            <template #title>Conteúdos vinculados</template>
            <template #content>
                <div class="grid gap-2 md:grid-cols-2">
                    <Link
                        v-for="content in plan.contents || []"
                        :key="content.id"
                        :href="route('contents.show', content.id)"
                        class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-left transition hover:border-indigo-300 hover:bg-indigo-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-indigo-700 dark:hover:bg-slate-800"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold">{{ content.title }}</p>
                                <p class="text-xs text-slate-500">{{ contentStatusLabel(content.status) }}</p>
                            </div>

                            <div class="text-right">
                                <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">{{ content.type?.name || '-' }}</p>
                                <div class="mt-1 flex justify-end gap-1">
                                    <Tag
                                        v-for="category in content.categories || []"
                                        :key="category.id"
                                        severity="secondary"
                                        class="!px-1 !py-0.5"
                                    >
                                        <template #default>
                                            <iconify-icon :icon="category.icon || 'mdi:shape-outline'" width="12" height="12" />
                                        </template>
                                    </Tag>
                                </div>
                            </div>
                        </div>
                    </Link>
                    <p v-if="!(plan.contents || []).length" class="text-sm text-slate-500">Nenhum conteúdo vinculado a este planejamento.</p>
                </div>
            </template>
        </Card>

        <TaskViewModal v-model:visible="taskModalVisible" :task="activeTask" />
    </div>
</template>
