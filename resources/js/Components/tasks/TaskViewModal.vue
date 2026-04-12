<script setup>
const props = defineProps({
    visible: { type: Boolean, default: false },
    task: { type: Object, default: null },
});

const emit = defineEmits(['update:visible']);
</script>

<template>
    <Dialog :visible="visible" modal header="Detalhes da tarefa" :style="{ width: '42rem', maxWidth: '96vw' }" @update:visible="emit('update:visible', $event)">
        <div v-if="task" class="space-y-4">
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
                    <p class="text-xs text-slate-500">Responsável</p>
                    <p class="font-medium">{{ task.assigned_user?.name || task.assignedUser?.name || 'Todos' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Prioridade</p>
                    <p class="font-medium">{{ task.priority }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Prazo</p>
                    <p class="font-medium">{{ task.due_date || '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Lembrete</p>
                    <p class="font-medium">{{ task.reminder_at || '-' }}</p>
                </div>
            </div>

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
        </div>

        <template #footer>
            <Button label="Fechar" outlined severity="secondary" @click="emit('update:visible', false)" />
        </template>
    </Dialog>
</template>
