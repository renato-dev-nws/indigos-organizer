<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoStatusTag from '@/Components/ui/BoStatusTag.vue';

defineOptions({ layout: AppLayout });
defineProps({ plan: Object });
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader :title="plan.title" subtitle="Detalhes do plano">
            <template #actions>
                <Link :href="route('plans.index')">
                    <Button class="hidden md:inline-flex" icon="pi pi-arrow-left" label="Voltar" outlined severity="secondary" />
                    <Button class="inline-flex md:hidden" icon="pi pi-arrow-left" rounded outlined severity="secondary" aria-label="Voltar" />
                </Link>
                <Link :href="route('plans.edit', plan.id)">
                    <Button class="hidden md:inline-flex" icon="pi pi-pencil" label="Editar" />
                    <Button class="inline-flex md:hidden" icon="pi pi-pencil" rounded aria-label="Editar" />
                </Link>
            </template>
        </BoPageHeader>

        <Card>
            <template #content>
                <div class="grid gap-4 md:grid-cols-2">
                    <div><p class="text-sm text-slate-500">Status</p><BoStatusTag :value="plan.status" /></div>
                    <div><p class="text-sm text-slate-500">Progresso</p><ProgressBar :value="plan.progress" style="height:0.6rem" /></div>
                </div>
            </template>
        </Card>

        <Card>
            <template #title>Fases e tarefas</template>
            <template #content>
                <div class="space-y-4">
                    <div v-for="phase in plan.phases" :key="phase.id" class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                        <h3 class="font-semibold">{{ phase.order }}. {{ phase.title }}</h3>
                        <p class="mb-2 text-sm text-slate-500">{{ phase.description || 'Sem descrição' }}</p>
                        <ul class="list-inside list-disc text-sm text-slate-600 dark:text-slate-300">
                            <li v-for="task in phase.tasks" :key="task.id">{{ task.title }}</li>
                        </ul>
                    </div>
                </div>
            </template>
        </Card>

        <Card>
            <template #title>Tarefas diretamente no plano</template>
            <template #content>
                <ul class="list-inside list-disc text-sm text-slate-600 dark:text-slate-300">
                    <li v-for="task in plan.tasks.filter((t) => !t.plan_phase_id)" :key="task.id">{{ task.title }}</li>
                    <li v-if="!plan.tasks.filter((t) => !t.plan_phase_id).length">Nenhuma tarefa vinculada diretamente.</li>
                </ul>
            </template>
        </Card>
    </div>
</template>
