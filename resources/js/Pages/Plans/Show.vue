<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
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

const activeTask = ref(null);
const taskModalVisible = ref(false);
const standaloneTasks = computed(() => (props.plan.tasks || []).filter((task) => !task.plan_phase_id));

const openTask = (task) => {
    activeTask.value = task;
    taskModalVisible.value = true;
};

const togglePhaseCompletion = (phase, completed) => {
    router.patch(
        route('plans.phases.completion', [props.plan.id, phase.id]),
        { completed: !!completed },
        { preserveScroll: true },
    );
};
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader :title="plan.title" subtitle="Detalhes do plano">
            <template #actions>
                <div>
                    <Button class="!hidden md:!inline-flex" icon="pi pi-arrow-left" label="Voltar" outlined severity="secondary" @click="goBack" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-arrow-left" rounded outlined severity="secondary" aria-label="Voltar" @click="goBack" />
                </div>
                <Link :href="route('plans.edit', plan.id)">
                    <Button class="!hidden md:!inline-flex" icon="pi pi-pencil" label="Editar" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-pencil" rounded aria-label="Editar" />
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
                            <Tag :value="`${plan.tasks?.length || 0} tarefas`" severity="secondary" />
                        </div>
                    </div>
                </div>
            </template>
        </Card>

        <Card>
            <template #title>Fases e tarefas</template>
            <template #content>
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
                        <div class="grid gap-2 md:grid-cols-2">
                            <button
                                v-for="task in phase.tasks"
                                :key="task.id"
                                type="button"
                                class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-left transition hover:border-indigo-300 hover:bg-indigo-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-indigo-700 dark:hover:bg-slate-800"
                                @click="openTask(task)"
                            >
                                <p class="text-sm font-semibold">{{ task.title }}</p>
                                <p class="text-xs text-slate-500">{{ task.status?.name || 'Sem status' }}</p>
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
                        <p class="text-sm font-semibold">{{ task.title }}</p>
                        <p class="text-xs text-slate-500">{{ task.status?.name || 'Sem status' }}</p>
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
                        <p class="text-sm font-semibold">{{ content.title }}</p>
                        <p class="text-xs text-slate-500">{{ content.status || 'Sem status' }}</p>
                    </Link>
                    <p v-if="!(plan.contents || []).length" class="text-sm text-slate-500">Nenhum conteúdo vinculado a este planejamento.</p>
                </div>
            </template>
        </Card>

        <TaskViewModal v-model:visible="taskModalVisible" :task="activeTask" />
    </div>
</template>
