<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    task: { type: Object, required: true },
});

const confirm = useConfirm();

const goBack = () => {
    if (typeof window !== 'undefined' && window.history.length > 1) {
        window.history.back();
    }
};

const assigneeLabel = computed(() => {
    const assignees = props.task?.assigned_users || props.task?.assignedUsers || [];
    if (!assignees.length) {
        return 'Todos';
    }

    return assignees.map((user) => user.name).join(', ');
});

const priorityLabels = {
    low: 'Baixa',
    medium: 'Média',
    high: 'Alta',
    urgent: 'Urgente',
};

const priorityLabel = computed(() => priorityLabels[props.task?.priority] || props.task?.priority || '-');

const relationLabel = computed(() => {
    if (!props.task?.related_type) {
        return '';
    }

    if (props.task.related_type === 'content' && props.task.content?.title) {
        return `Conteúdo: ${props.task.content.title}`;
    }

    if (props.task.related_type === 'plan' && props.task.plan?.title) {
        const phase = props.task.planPhase?.title ? ` / Fase: ${props.task.planPhase.title}` : '';
        return `Planejamento: ${props.task.plan.title}${phase}`;
    }

    if (props.task.related_type === 'event' && props.task.event?.title) {
        return `Evento: ${props.task.event.title}`;
    }

    if (props.task.related_type === 'administrative') {
        return 'Administrativo';
    }

    return '';
});

const formatDateTime = (value) => {
    if (!value) {
        return '-';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    return date.toLocaleString('pt-BR');
};

const formatDate = (value) => {
    if (!value) {
        return '-';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    return date.toLocaleDateString('pt-BR');
};

const isCompleted = computed(() => {
    const statusName = String(props.task?.status?.name || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase();

    return ['conclu', 'finaliz', 'done', 'completed'].some((token) => statusName.includes(token));
});

const completeTask = () => {
    if (!props.task?.id || isCompleted.value) {
        return;
    }

    confirm.require({
        header: 'Confirmar ação',
        message: 'Confirma concluir esta tarefa?',
        icon: 'pi pi-question-circle',
        rejectLabel: 'Cancelar',
        acceptLabel: 'Confirmar',
        acceptClass: 'p-button-sm',
        rejectClass: 'p-button-text p-button-sm',
        accept: () => {
            router.patch(route('tasks.quick-action', props.task.id), { action: 'complete' }, { preserveScroll: true });
        },
    });
};
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Detalhes da tarefa" supratitle="TAREFAS" subtitle="" icon="mdi:text-box-search-outline">
            <template #actions>
                <div class="flex items-center gap-2">
                    <Button class="!hidden md:!inline-flex" label="Voltar" icon="pi pi-arrow-left" outlined severity="secondary" @click="goBack" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-arrow-left" rounded outlined severity="secondary" aria-label="Voltar" @click="goBack" />
                    <Link :href="route('tasks.edit', task.id)">
                        <Button class="!hidden md:!inline-flex" label="Editar" icon="pi pi-pencil" outlined severity="secondary" />
                        <Button class="!inline-flex md:!hidden" icon="pi pi-pencil" rounded outlined severity="secondary" aria-label="Editar" />
                    </Link>
                </div>
            </template>
        </BoPageHeader>

        <div class="mx-auto max-w-[700px] space-y-4">
            <Card>
                <template #content>
                    <div class="space-y-4">
                        <div>
                            <h3 class="text-lg font-semibold">{{ task.title }}</h3>
                            <p class="text-sm text-slate-500">{{ task.description || 'Sem descrição.' }}</p>
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            <div>
                                <p class="text-xs text-slate-500">Status</p>
                                <p class="font-medium">{{ task.status?.name || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Responsáveis</p>
                                <p class="font-medium">{{ assigneeLabel }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Prioridade</p>
                                <p class="font-medium">{{ priorityLabel }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Agendado para</p>
                                <p class="font-medium">{{ formatDateTime(task.scheduled_for) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Prazo</p>
                                <p class="font-medium">{{ formatDate(task.due_date) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Lembrete</p>
                                <p class="font-medium">{{ formatDateTime(task.reminder_at) }}</p>
                            </div>
                            <div v-if="relationLabel" class="md:col-span-2">
                                <p class="text-xs text-slate-500">Relacionamento</p>
                                <p class="font-medium">{{ relationLabel }}</p>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <Card>
                <template #title>Subtarefas</template>
                <template #content>
                    <ul class="list-inside list-disc space-y-1 text-sm">
                        <li v-for="subtask in task.subtasks || []" :key="subtask.id || subtask.title">
                            {{ subtask.title }}
                        </li>
                        <li v-if="!(task.subtasks || []).length">Nenhuma subtarefa.</li>
                    </ul>
                </template>
            </Card>

            <div class="flex justify-end gap-2">
                <Button
                    v-if="!isCompleted"
                    label="Marcar como concluída"
                    icon="pi pi-check"
                    severity="success"
                    @click="completeTask"
                />
                <Link :href="route('tasks.index')">
                    <Button type="button" label="Fechar" outlined severity="secondary" />
                </Link>
            </div>
        </div>
    </div>
</template>
