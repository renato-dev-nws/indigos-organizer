<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFormSection from '@/Components/ui/BoFormSection.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';

const props = defineProps({ statuses: Array, contents: Array });

defineOptions({ layout: AppLayout });

const form = useForm({
    title: '',
    description: '',
    type: 'content',
    content_id: null,
    task_status_id: props.statuses?.[0]?.id,
    assignee: '',
    priority: 'medium',
    due_date: '',
    subtasks: [],
});

const submit = () => form.post(route('tasks.store'));

const addSubtask = () => {
    form.subtasks.push({ title: '', completed: false, order: form.subtasks.length });
};

const removeSubtask = (index) => {
    form.subtasks.splice(index, 1);
};
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Nova tarefa" subtitle="Registre tarefas operacionais e de conteúdo">
            <template #actions>
                <Link :href="route('tasks.index')">
                    <Button label="Voltar" icon="pi pi-arrow-left" outlined severity="secondary" />
                </Link>
            </template>
        </BoPageHeader>

        <form class="space-y-4" @submit.prevent="submit">
            <BoFormSection title="Dados da tarefa" description="Dados essenciais para acompanhamento">
                <div class="md:col-span-2 space-y-2">
                    <label for="task-title">Título</label>
                    <InputText id="task-title" v-model="form.title" fluid :invalid="!!form.errors.title" />
                    <Message v-if="form.errors.title" severity="error" size="small" variant="simple">{{ form.errors.title }}</Message>
                </div>

                <div class="md:col-span-2 space-y-2">
                    <label for="task-description">Descrição</label>
                    <Textarea id="task-description" v-model="form.description" rows="4" fluid :invalid="!!form.errors.description" />
                </div>

                <div class="space-y-2">
                    <label for="task-type">Tipo</label>
                    <Select
                        id="task-type"
                        v-model="form.type"
                        :options="[
                            { label: 'Conteúdo', value: 'content' },
                            { label: 'Administrativa', value: 'administrative' },
                        ]"
                        option-label="label"
                        option-value="value"
                        fluid
                    />
                </div>

                <div class="space-y-2">
                    <label for="task-content">Conteúdo relacionado</label>
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
                    <Message v-if="form.errors.content_id" severity="error" size="small" variant="simple">{{ form.errors.content_id }}</Message>
                </div>

                <div class="space-y-2">
                    <label for="task-status">Status</label>
                    <Select id="task-status" v-model="form.task_status_id" :options="statuses" option-label="name" option-value="id" fluid />
                </div>

                <div class="space-y-2">
                    <label for="task-priority">Prioridade</label>
                    <Select
                        id="task-priority"
                        v-model="form.priority"
                        :options="[
                            { label: 'Baixa', value: 'low' },
                            { label: 'Média', value: 'medium' },
                            { label: 'Alta', value: 'high' },
                            { label: 'Urgente', value: 'urgent' },
                        ]"
                        option-label="label"
                        option-value="value"
                        fluid
                    />
                </div>

                <div class="space-y-2">
                    <label for="task-assignee">Responsável</label>
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
                    <div class="space-y-2">
                        <div v-for="(subtask, index) in form.subtasks" :key="index" class="flex items-center gap-2 rounded-xl border border-slate-200/80 p-2 dark:border-slate-800">
                            <Checkbox v-model="subtask.completed" binary />
                            <InputText v-model="subtask.title" class="w-full" placeholder="Título da subtarefa" />
                            <Button type="button" icon="pi pi-trash" text severity="danger" @click="removeSubtask(index)" />
                        </div>
                        <Button type="button" icon="pi pi-plus" label="Adicionar subtarefa" outlined @click="addSubtask" />
                    </div>
                </template>
            </Card>

            <div class="flex justify-end gap-2">
                <Link :href="route('tasks.index')">
                    <Button type="button" label="Cancelar" outlined severity="secondary" />
                </Link>
                <Button type="submit" :loading="form.processing" label="Salvar tarefa" icon="pi pi-save" />
            </div>
        </form>
    </div>
</template>
