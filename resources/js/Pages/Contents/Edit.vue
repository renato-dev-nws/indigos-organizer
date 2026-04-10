<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFormSection from '@/Components/ui/BoFormSection.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoConfirmButton from '@/Components/ui/BoConfirmButton.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({ content: Object, platforms: Array, types: Array, categories: Array, ideas: Array });

const form = useForm({
    idea_id: props.content.idea_id,
    title: props.content.title,
    script: props.content.script,
    content_platform_id: props.content.content_platform_id,
    content_type_id: props.content.content_type_id,
    status: props.content.status,
    content_category_ids: props.content.categories?.map((c) => c.id) ?? [],
    links: props.content.links ?? [],
    planned_publish_at: props.content.planned_publish_at,
    published_at: props.content.published_at,
});

const submit = () => form.put(route('contents.update', props.content.id));

const addLink = () => {
    form.links.push({ title: '', url: '' });
};

const removeLink = (index) => {
    form.links.splice(index, 1);
};

const uploadFile = async ({ files }) => {
    if (!files?.length) {
        return;
    }

    for (const file of files) {
        router.post(
            route('contents.files.store', props.content.id),
            { file },
            {
                forceFormData: true,
                preserveScroll: true,
            },
        );
    }
};

const removeFile = (fileId) => {
    router.delete(route('contents.files.destroy', [props.content.id, fileId]), { preserveScroll: true });
};
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Editar conteudo" subtitle="Atualize roteiro, links e anexos">
            <template #actions>
                <Link :href="route('contents.show', content.id)">
                    <Button label="Visualizar" icon="pi pi-eye" outlined severity="secondary" />
                </Link>
            </template>
        </BoPageHeader>

        <form class="space-y-4" @submit.prevent="submit">
            <BoFormSection title="Dados principais" description="Informacoes editoriais e planejamento">
                <div class="md:col-span-2 space-y-2">
                    <label for="content-title">Titulo</label>
                    <InputText id="content-title" v-model="form.title" fluid :invalid="!!form.errors.title" />
                    <Message v-if="form.errors.title" severity="error" size="small" variant="simple">{{ form.errors.title }}</Message>
                </div>

                <div class="space-y-2">
                    <label for="content-idea">Ideia de origem</label>
                    <Select id="content-idea" v-model="form.idea_id" :options="ideas" option-label="title" option-value="id" show-clear fluid />
                </div>

                <div class="space-y-2">
                    <label for="content-status">Status</label>
                    <Select id="content-status" v-model="form.status" :options="['queued', 'in_production', 'cancelled', 'paused', 'published']" />
                </div>

                <div class="space-y-2">
                    <label for="content-platform">Plataforma</label>
                    <Select id="content-platform" v-model="form.content_platform_id" :options="platforms" option-label="name" option-value="id" show-clear fluid />
                </div>

                <div class="space-y-2">
                    <label for="content-type">Tipo</label>
                    <Select id="content-type" v-model="form.content_type_id" :options="types" option-label="name" option-value="id" show-clear fluid />
                </div>

                <div class="md:col-span-2 space-y-2">
                    <label for="content-categories">Categorias</label>
                    <MultiSelect id="content-categories" v-model="form.content_category_ids" :options="categories" option-label="name" option-value="id" display="chip" fluid />
                </div>

                <div class="space-y-2">
                    <label for="planned-publish">Publicacao planejada</label>
                    <DatePicker id="planned-publish" v-model="form.planned_publish_at" show-time hour-format="24" fluid />
                </div>

                <div class="space-y-2">
                    <label for="published-at">Publicado em</label>
                    <DatePicker id="published-at" v-model="form.published_at" show-time hour-format="24" fluid />
                </div>

                <div class="md:col-span-2 space-y-2">
                    <label for="content-script">Roteiro</label>
                    <Editor id="content-script" v-model="form.script" editor-style="height: 220px" />
                </div>
            </BoFormSection>

            <Card>
                <template #title>Links externos</template>
                <template #content>
                    <div class="space-y-3">
                        <div v-for="(link, index) in form.links" :key="index" class="grid gap-3 rounded-xl border border-slate-200/80 p-3 md:grid-cols-2 dark:border-slate-800">
                            <InputText v-model="link.title" placeholder="Titulo" />
                            <div class="flex gap-2">
                                <InputText v-model="link.url" class="w-full" placeholder="https://..." />
                                <Button type="button" icon="pi pi-trash" text severity="danger" aria-label="Remover link" @click="removeLink(index)" />
                            </div>
                        </div>
                        <Button type="button" icon="pi pi-plus" label="Adicionar link" outlined @click="addLink" />
                    </div>
                </template>
            </Card>

            <Card>
                <template #title>Anexos</template>
                <template #content>
                    <FileUpload mode="basic" custom-upload auto choose-label="Enviar arquivo" @uploader="uploadFile" />
                    <DataTable class="mt-4" :value="content.files" data-key="id" striped-rows size="small">
                        <Column field="original_name" header="Arquivo" />
                        <Column field="mime_type" header="MIME" />
                        <Column field="size" header="Tamanho" />
                        <Column header="Acoes">
                            <template #body="{ data }">
                                <BoConfirmButton label="Remover" icon="pi pi-trash" severity="danger" message="Deseja remover este arquivo?" @confirm="removeFile(data.id)" />
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <div class="flex justify-end gap-2">
                <Link :href="route('contents.index')">
                    <Button type="button" label="Cancelar" outlined severity="secondary" />
                </Link>
                <Button type="submit" :loading="form.processing" label="Atualizar conteudo" icon="pi pi-save" />
            </div>
        </form>
    </div>
</template>
