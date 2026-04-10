<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoStatusTag from '@/Components/ui/BoStatusTag.vue';

defineOptions({ layout: AppLayout });
defineProps({ idea: Object });
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader :title="idea.title" subtitle="Detalhes completos da ideia">
            <template #actions>
                <Link :href="route('ideas.edit', idea.id)">
                    <Button icon="pi pi-pencil" label="Editar" />
                </Link>
            </template>
        </BoPageHeader>

        <Card>
            <template #content>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Status</p>
                        <BoStatusTag :value="idea.status" />
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Tipo</p>
                        <p class="font-semibold">{{ idea.type?.name || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Categoria</p>
                        <p class="font-semibold">{{ idea.category?.name || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Descricao</p>
                        <p>{{ idea.description || '-' }}</p>
                    </div>
                </div>
            </template>
        </Card>

        <Card>
            <template #title>Referencias</template>
            <template #content>
                <DataTable :value="idea.references" data-key="id" striped-rows>
                    <Column field="title" header="Titulo" />
                    <Column field="description" header="Descricao" />
                    <Column header="URL">
                        <template #body="{ data }">
                            <a :href="data.url" target="_blank" rel="noopener" class="text-cyan-600 underline dark:text-cyan-400">{{ data.url }}</a>
                        </template>
                    </Column>
                </DataTable>
            </template>
        </Card>
    </div>
</template>
