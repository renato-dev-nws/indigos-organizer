<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoStatusTag from '@/Components/ui/BoStatusTag.vue';
import BoDateText from '@/Components/ui/BoDateText.vue';

defineOptions({ layout: AppLayout });

const goBack = () => {
    if (typeof window !== 'undefined' && window.history.length > 1) {
        window.history.back();
    }
};
defineProps({ content: Object });
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader :title="content.title" subtitle="Visão completa do conteúdo">
            <template #actions>
                <div>
                    <Button class="!hidden md:!inline-flex" icon="pi pi-arrow-left" label="Voltar" outlined severity="secondary" @click="goBack" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-arrow-left" rounded outlined severity="secondary" aria-label="Voltar" @click="goBack" />
                </div>
                <Link :href="route('contents.edit', content.id)">
                    <Button class="!hidden md:!inline-flex" icon="pi pi-pencil" label="Editar" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-pencil" rounded aria-label="Editar" />
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
                        <p class="text-sm text-slate-500 dark:text-slate-400">Plataformas</p>
                        <div class="mt-1 flex flex-wrap gap-1">
                            <Tag v-for="platform in content.platforms" :key="platform.id" :value="platform.name" severity="secondary" />
                            <span v-if="!content.platforms?.length" class="font-semibold">-</span>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Tipo</p>
                        <p class="font-semibold">{{ content.type?.name || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Estilos</p>
                        <div class="mt-1 flex flex-wrap gap-1">
                            <Tag v-for="style in content.styles || []" :key="style.id" :value="style.name" severity="secondary" />
                            <span v-if="!content.styles?.length" class="font-semibold">-</span>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">Publicação planejada</p>
                        <p class="font-semibold"><BoDateText :value="content.planned_publish_at" mode="datetime" /></p>
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
                        <Column field="title" header="Título" />
                        <Column header="URL">
                            <template #body="{ data }">
                                <a :href="data.url" target="_blank" rel="noopener" class="text-indigo-600 underline dark:text-indigo-400">{{ data.url }}</a>
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
                        <Column header="Ações" class="w-24">
                            <template #body="{ data }">
                                <a v-if="data.url" :href="data.url" target="_blank" rel="noopener" class="text-indigo-600 underline dark:text-indigo-400">Abrir</a>
                                <span v-else>-</span>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
        </div>
    </div>
</template>
