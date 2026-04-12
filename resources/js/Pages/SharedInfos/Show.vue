<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';

defineOptions({ layout: AppLayout });
defineProps({ sharedInfo: Object });
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader :title="sharedInfo.title" subtitle="Detalhes da informação compartilhada">
            <template #actions>
                <Link :href="route('shared-infos.index')">
                    <Button class="!hidden md:!inline-flex" icon="pi pi-arrow-left" label="Voltar" outlined severity="secondary" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-arrow-left" rounded outlined severity="secondary" aria-label="Voltar" />
                </Link>
                <Link :href="route('shared-infos.edit', sharedInfo.id)">
                    <Button class="!hidden md:!inline-flex" icon="pi pi-pencil" label="Editar" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-pencil" rounded aria-label="Editar" />
                </Link>
            </template>
        </BoPageHeader>

        <Card>
            <template #content>
                <p>{{ sharedInfo.description || '-' }}</p>
            </template>
        </Card>

        <Card>
            <template #title>Links</template>
            <template #content>
                <DataTable :value="sharedInfo.links" data-key="id" striped-rows>
                    <Column field="title" header="Título" />
                    <Column field="description" header="Descrição" />
                    <Column header="URL">
                        <template #body="{ data }"><a :href="data.url" target="_blank" rel="noopener" class="text-indigo-600 underline dark:text-indigo-400">{{ data.url }}</a></template>
                    </Column>
                </DataTable>
            </template>
        </Card>

        <Card>
            <template #title>Documentos</template>
            <template #content>
                <DataTable :value="sharedInfo.documents" data-key="id" striped-rows>
                    <Column field="original_name" header="Arquivo" />
                    <Column field="mime_type" header="MIME" />
                    <Column header="Download">
                        <template #body="{ data }"><a :href="data.url" target="_blank" rel="noopener" class="text-indigo-600 underline dark:text-indigo-400">Abrir</a></template>
                    </Column>
                </DataTable>
            </template>
        </Card>
    </div>
</template>
