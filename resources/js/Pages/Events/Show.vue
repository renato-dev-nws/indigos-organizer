<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoDateText from '@/Components/ui/BoDateText.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import TaskViewModal from '@/Components/tasks/TaskViewModal.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({ event: Object });
const activeTask = ref(null);
const taskModalVisible = ref(false);

const attendanceLabels = {
    participant: 'Como participante',
    audience: 'Como público',
};

const ticketTags = computed(() => ([
    { label: '1º lote', value: props.event.ticket_price_first_batch },
    { label: '2º lote', value: props.event.ticket_price_second_batch },
    { label: '3º lote', value: props.event.ticket_price_third_batch },
    { label: 'Na porta', value: props.event.ticket_price_door },
]).filter((item) => item.value !== null && item.value !== '' && item.value !== undefined));

const openTask = (task) => {
    activeTask.value = task;
    taskModalVisible.value = true;
};
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader :title="event.title" subtitle="Visão completa do evento">
            <template #actions>
                <Link :href="route('events.index')">
                    <Button class="!hidden md:!inline-flex" icon="pi pi-arrow-left" label="Voltar" outlined severity="secondary" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-arrow-left" rounded outlined severity="secondary" aria-label="Voltar" />
                </Link>
                <Link :href="route('events.edit', event.id)">
                    <Button class="!hidden md:!inline-flex" icon="pi pi-pencil" label="Editar" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-pencil" rounded aria-label="Editar" />
                </Link>
            </template>
        </BoPageHeader>

        <Card>
            <template #content>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Tipo</p>
                        <p class="font-semibold">{{ event.type?.name || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Presença</p>
                        <p class="font-semibold">{{ attendanceLabels[event.attendance_mode] || event.attendance_mode }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Data e hora</p>
                        <p class="font-semibold"><BoDateText :value="event.starts_at" mode="datetime" /></p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Local</p>
                        <p class="font-semibold">{{ event.venue?.name || '-' }}</p>
                    </div>
                </div>
            </template>
        </Card>

        <Card>
            <template #title>Descrição</template>
            <template #content>
                <p class="whitespace-pre-line text-sm text-slate-700 dark:text-slate-200">{{ event.description || 'Sem descrição cadastrada.' }}</p>
            </template>
        </Card>

        <div class="grid gap-4 xl:grid-cols-2">
            <Card>
                <template #title>Ingressos</template>
                <template #content>
                    <div class="space-y-3 text-sm">
                        <a v-if="event.ticket_link" :href="event.ticket_link" target="_blank" rel="noopener" class="text-indigo-600 underline dark:text-indigo-400">{{ event.ticket_link }}</a>
                        <p v-else class="text-slate-500">Sem link de ingresso.</p>
                        <div class="flex flex-wrap gap-2">
                            <Tag v-for="ticket in ticketTags" :key="ticket.label" :value="`${ticket.label}: R$ ${Number(ticket.value).toFixed(2)}`" severity="secondary" />
                            <span v-if="!ticketTags.length" class="text-slate-500">Nenhum valor informado.</span>
                        </div>
                    </div>
                </template>
            </Card>

            <Card>
                <template #title>Local</template>
                <template #content>
                    <div class="space-y-2 text-sm">
                        <p><strong>Nome:</strong> {{ event.venue?.name || '-' }}</p>
                        <p><strong>Endereço:</strong> {{ event.venue?.address_line || '-' }}, {{ event.venue?.address_number || '-' }}</p>
                        <p><strong>Bairro:</strong> {{ event.venue?.neighborhood || '-' }}</p>
                        <p><strong>Cidade/UF:</strong> {{ event.venue?.city || '-' }} / {{ event.venue?.state || '-' }}</p>
                    </div>
                </template>
            </Card>
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            <Card>
                <template #title>Informações extras</template>
                <template #content>
                    <div class="space-y-3">
                        <div v-for="item in event.extra_infos || []" :key="item.id" class="rounded-xl border border-slate-200/80 p-3 dark:border-slate-800">
                            <p class="font-semibold">{{ item.title }}</p>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ item.information }}</p>
                        </div>
                        <p v-if="!(event.extra_infos || []).length" class="text-sm text-slate-500">Nenhuma informação extra cadastrada.</p>
                    </div>
                </template>
            </Card>

            <Card>
                <template #title>Outros links</template>
                <template #content>
                    <div class="space-y-3">
                        <div v-for="link in event.links || []" :key="link.id" class="rounded-xl border border-slate-200/80 p-3 dark:border-slate-800">
                            <p class="font-semibold">{{ link.title }}</p>
                            <a :href="link.url" target="_blank" rel="noopener" class="mt-1 block text-sm text-indigo-600 underline dark:text-indigo-400">{{ link.url }}</a>
                        </div>
                        <p v-if="!(event.links || []).length" class="text-sm text-slate-500">Nenhum link cadastrado.</p>
                    </div>
                </template>
            </Card>
        </div>

        <Card>
            <template #title>Tarefas relacionadas</template>
            <template #content>
                <div class="grid gap-2 md:grid-cols-2">
                    <button
                        v-for="task in event.tasks || []"
                        :key="task.id"
                        type="button"
                        class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-left transition hover:border-indigo-300 hover:bg-indigo-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-indigo-700 dark:hover:bg-slate-800"
                        @click="openTask(task)"
                    >
                        <p class="text-sm font-semibold">{{ task.title }}</p>
                        <p class="text-xs text-slate-500">{{ task.status?.name || 'Sem status' }}</p>
                    </button>
                    <p v-if="!(event.tasks || []).length" class="text-sm text-slate-500">Nenhuma tarefa vinculada diretamente.</p>
                </div>
            </template>
        </Card>

        <TaskViewModal v-model:visible="taskModalVisible" :task="activeTask" />
    </div>
</template>