<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoStatusTag from '@/Components/ui/BoStatusTag.vue';

defineOptions({ layout: AppLayout });
defineProps({ content: Object });
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader :title="content.title" subtitle="Visao completa do conteudo">
            <template #actions>
                <Link :href="route('contents.edit', content.id)">
                    <Button icon="pi pi-pencil" label="Editar" />
                </Link>
            </template>
        </BoPageHeader>

        <Card>
            <template #content>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Status</p>
                        <BoStatusTag :value="content.status" />
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Plataforma</p>
                        <p class="font-semibold">{{ content.platform?.name || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Tipo</p>
                        <p class="font-semibold">{{ content.type?.name || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Publicacao planejada</p>
                        <p class="font-semibold">{{ content.planned_publish_at || '-' }}</p>
                    </div>
                </div>
            </template>
        </Card>

        <Card>
            <template #title>Roteiro</template>
            <template #content>
                <div class="prose max-w-none dark:prose-invert" v-html="content.script || '<p>-</p>'" />
            </template>
        </Card>

        <div class="grid gap-4 xl:grid-cols-2">
            <Card>
                <template #title>Links</template>
                <template #content>
                    <DataTable :value="content.links" data-key="id" size="small">
                        <Column field="title" header="Titulo" />
                        <Column header="URL">
                            <template #body="{ data }">
                                <a :href="data.url" target="_blank" rel="noopener" class="text-cyan-600 underline dark:text-cyan-400">{{ data.url }}</a>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <Card>
                <template #title>Anexos</template>
                <template #content>
                    <DataTable :value="content.files" data-key="id" size="small">
                        <Column field="original_name" header="Arquivo" />
                        <Column field="mime_type" header="MIME" />
                        <Column field="size" header="Tamanho" />
                    </DataTable>
                </template>
            </Card>
        </div>
    </div>
</template>
