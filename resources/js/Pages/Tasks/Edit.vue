<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import draggable from 'vuedraggable';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFormSection from '@/Components/ui/BoFormSection.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoConfirmButton from '@/Components/ui/BoConfirmButton.vue';

const props = defineProps({ task: Object, statuses: Array, contents: Array });
defineOptions({ layout: AppLayout });

const form = useForm({
    title: props.task.title,
    description: props.task.description,
    type: props.task.type,
    content_id: props.task.content_id,
    task_status_id: props.task.task_status_id,
    assignee: props.task.assignee,
    priority: props.task.priority,
    due_date: props.task.due_date,
});

const submit = () => form.put(route('tasks.update', props.task.id));

const newSubtask = useForm({ title: '', completed: false, order: (props.task.subtasks?.length ?? 0) + 1 });

const addSubtask = () => {
    newSubtask.post(route('tasks.subtasks.store', props.task.id), {
        preserveScroll: true,
        onSuccess: () => newSubtask.reset(),
    });
};

const updateSubtask = (subtask, changes = {}) => {
    router.patch(
        route('tasks.subtasks.update', [props.task.id, subtask.id]),
        {
            title: subtask.title,
            completed: subtask.completed,
            order: subtask.order,
            ...changes,
        },
        { preserveScroll: true },
    );
};

const reorderSubtasks = () => {
    props.task.subtasks.forEach((subtask, index) => {
        router.patch(
            route('tasks.subtasks.update', [props.task.id, subtask.id]),
            {
                title: subtask.title,
                completed: subtask.completed,
                order: index,
            },
            { preserveScroll: true },
        );
    });
};

const removeSubtask = (subtaskId) => {
    router.delete(route('tasks.subtasks.destroy', [props.task.id, subtaskId]), { preserveScroll: true });
};
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Editar tarefa" subtitle="Atualize dados e subtarefas">
            <template #actions>
                <Link :href="route('tasks.index')">
                    <Button label="Voltar" icon="pi pi-arrow-left" outlined severity="secondary" />
                </Link>
            </template>
        </BoPageHeader>

        <form class="space-y-4" @submit.prevent="submit">
            <BoFormSection title="Dados da tarefa" description="Ajuste status, prioridade e atribuicao">
                <div class="md:col-span-2 space-y-2">
                    <label for="task-title">Titulo</label>
                    <InputText id="task-title" v-model="form.title" fluid :invalid="!!form.errors.title" />
                </div>

                <div class="md:col-span-2 space-y-2">
                    <label for="task-description">Descricao</label>
                    <Textarea id="task-description" v-model="form.description" rows="4" fluid />
                </div>

                <div class="space-y-2">
                    <label for="task-type">Tipo</label>
                    <Select id="task-type" v-model="form.type" :options="['content', 'administrative']" />
                </div>

                <div class="space-y-2">
                    <label for="task-content">Conteudo relacionado</label>
                    <Select
                        id="task-content"
                        v-model="form.content_id"
                        :options="contents"
                        option-label="title"
                        option-value="id"
                        :disabled="form.type !== 'content'"
                        :invalid="!!form.errors.content_id"
                        show-clear
                        fluid
                    />
                </div>

                <div class="space-y-2">
                    <label for="task-status">Status</label>
                    <Select id="task-status" v-model="form.task_status_id" :options="statuses" option-label="name" option-value="id" fluid />
                </div>

                <div class="space-y-2">
                    <label for="task-priority">Prioridade</label>
                    <Select id="task-priority" v-model="form.priority" :options="['low', 'medium', 'high', 'urgent']" fluid />
                </div>

                <div class="space-y-2">
                    <label for="task-assignee">Responsavel</label>
                    <InputText id="task-assignee" v-model="form.assignee" fluid />
                </div>

                <div class="space-y-2">
                    <label for="task-due-date">Prazo</label>
                    <DatePicker id="task-due-date" v-model="form.due_date" fluid />
                </div>
            </BoFormSection>

            <Card>
                <template #title>Subtarefas</template>
                <template #content>
                    <draggable :list="task.subtasks" item-key="id" class="space-y-2" @change="reorderSubtasks">
                        <template #item="{ element }">
                            <div class="flex items-center gap-2 rounded-xl border border-slate-200/80 p-2 dark:border-slate-800">
                                <Checkbox :model-value="element.completed" binary @update:model-value="(value) => updateSubtask(element, { completed: value })" />
                                <InputText v-model="element.title" class="w-full" @blur="updateSubtask(element)" />
                                <BoConfirmButton label="Remover" icon="pi pi-trash" severity="danger" message="Remover subtarefa?" @confirm="removeSubtask(element.id)" />
                            </div>
                        </template>
                    </draggable>

                    <div class="mt-3 flex items-center gap-2">
                        <InputText v-model="newSubtask.title" class="w-full" placeholder="Nova subtarefa" />
                        <Button type="button" icon="pi pi-plus" label="Adicionar" @click="addSubtask" />
                    </div>
                </template>
            </Card>

            <div class="flex justify-end gap-2">
                <Link :href="route('tasks.index')">
                    <Button type="button" label="Cancelar" outlined severity="secondary" />
                </Link>
                <Button type="submit" :loading="form.processing" label="Atualizar tarefa" icon="pi pi-save" />
            </div>
        </form>
    </div>
</template>
