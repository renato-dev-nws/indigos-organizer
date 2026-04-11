<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFormSection from '@/Components/ui/BoFormSection.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import AppFileUpload from '@/Components/AppFileUpload.vue';

defineOptions({ layout: AppLayout });

const form = useForm({ title: '', description: '', links: [], documents: [] });

const addLink = () => form.links.push({ title: '', url: '', description: '' });
const removeLink = (index) => form.links.splice(index, 1);
const submit = () => form.post(route('shared-infos.store'), { forceFormData: true });
const uploadFile = (files) => { form.documents = files; };
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Nova informação" subtitle="Compartilhe links e documentos com a banda">
            <template #actions>
                <Link :href="route('shared-infos.index')"><Button label="Voltar" icon="pi pi-arrow-left" outlined severity="secondary" /></Link>
            </template>
        </BoPageHeader>

        <form class="space-y-4" @submit.prevent="submit">
            <BoFormSection title="Dados principais" description="Título e descrição da informação">
                <div class="md:col-span-2 space-y-2"><label>Título</label><InputText v-model="form.title" fluid /></div>
                <div class="md:col-span-2 space-y-2"><label>Descrição</label><Textarea v-model="form.description" rows="4" fluid /></div>
            </BoFormSection>

            <Card>
                <template #title>Links</template>
                <template #content>
                    <div class="space-y-3">
                        <div v-for="(link, index) in form.links" :key="index" class="grid gap-3 md:grid-cols-3">
                            <InputText v-model="link.title" placeholder="Título" />
                            <InputText v-model="link.url" placeholder="https://..." />
                            <div class="flex gap-2">
                                <InputText v-model="link.description" class="w-full" placeholder="Descrição" />
                                <Button type="button" icon="pi pi-trash" text severity="danger" @click="removeLink(index)" />
                            </div>
                        </div>
                        <Button type="button" icon="pi pi-plus" label="Adicionar link" outlined @click="addLink" />
                    </div>
                </template>
            </Card>

            <Card>
                <template #title>Documentos</template>
                <template #content>
                    <AppFileUpload :files="[]" @upload="uploadFile" />
                </template>
            </Card>

            <div class="flex justify-end gap-2">
                <Link :href="route('shared-infos.index')"><Button type="button" label="Cancelar" outlined severity="secondary" /></Link>
                <Button type="submit" :loading="form.processing" label="Salvar informação" icon="pi pi-save" />
            </div>
        </form>
    </div>
</template>
