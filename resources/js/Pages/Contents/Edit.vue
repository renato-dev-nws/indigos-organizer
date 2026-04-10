<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFormSection from '@/Components/ui/BoFormSection.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import AppRichText from '@/Components/AppRichText.vue';
import AppFileUpload from '@/Components/AppFileUpload.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({ content: Object, platforms: Array, types: Array, categories: Array, ideas: Array });

const form = useForm({
    idea_id: props.content.idea_id,
    title: props.content.title,
    script: props.content.script,
    content_platform_id: props.content.content_platform_id,
    idea_type_id: props.content.idea_type_id,
    idea_category_id: props.content.idea_category_id,
    status: props.content.status,
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

const uploadFile = async (files) => {
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
        <BoPageHeader title="Editar conteúdo" subtitle="Atualize roteiro, links e anexos">
            <template #actions>
                <Link :href="route('contents.show', content.id)">
                    <Button label="Visualizar" icon="pi pi-eye" outlined severity="secondary" />
                </Link>
            </template>
        </BoPageHeader>

        <form class="space-y-4" @submit.prevent="submit">
            <BoFormSection title="Dados principais" description="Informações editoriais e planejamento">
                <div class="md:col-span-2 space-y-2">
                    <label for="content-title">Título</label>
                    <InputText id="content-title" v-model="form.title" fluid :invalid="!!form.errors.title" />
                    <Message v-if="form.errors.title" severity="error" size="small" variant="simple">{{ form.errors.title }}</Message>
                </div>

                <div class="space-y-2">
                    <label for="content-idea">Ideia de origem</label>
                    <Select id="content-idea" v-model="form.idea_id" :options="ideas" option-label="title" option-value="id" show-clear fluid />
                </div>

                <div class="space-y-2">
                    <label for="content-status">Status</label>
                    <Select
                        id="content-status"
                        v-model="form.status"
                        :options="[
                            { label: 'Na fila', value: 'queued' },
                            { label: 'Em produção', value: 'in_production' },
                            { label: 'Cancelado', value: 'cancelled' },
                            { label: 'Pausado', value: 'paused' },
                            { label: 'Publicado', value: 'published' },
                        ]"
                        option-label="label"
                        option-value="value"
                        fluid
                    />
                </div>

                <div class="space-y-2">
                    <label for="content-platform">Plataforma</label>
                    <Select id="content-platform" v-model="form.content_platform_id" :options="platforms" option-label="name" option-value="id" show-clear fluid />
                </div>

                <div class="space-y-2">
                    <label for="content-type">Tipo</label>
                    <Select id="content-type" v-model="form.idea_type_id" :options="types" option-label="name" option-value="id" show-clear fluid />
                </div>

                <div class="space-y-2">
                    <label for="content-category">Categoria</label>
                    <Select id="content-category" v-model="form.idea_category_id" :options="categories" option-label="name" option-value="id" show-clear fluid />
                </div>

                <div class="space-y-2">
                    <label for="planned-publish">Publicação planejada</label>
                    <DatePicker id="planned-publish" v-model="form.planned_publish_at" show-time hour-format="24" fluid />
                </div>

                <div class="space-y-2">
                    <label for="published-at">Publicado em</label>
                    <DatePicker id="published-at" v-model="form.published_at" show-time hour-format="24" fluid />
                </div>

                <div class="md:col-span-2 space-y-2">
                    <label for="content-script">Roteiro</label>
                    <AppRichText id="content-script" v-model="form.script" :min-height="240" />
                </div>
            </BoFormSection>

            <Card>
                <template #title>Links externos</template>
                <template #content>
                    <div class="space-y-3">
                        <div v-for="(link, index) in form.links" :key="index" class="grid gap-3 rounded-xl border border-slate-200/80 p-3 md:grid-cols-2 dark:border-slate-800">
                            <InputText v-model="link.title" placeholder="Título" />
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
                    <AppFileUpload :files="content.files" @upload="uploadFile" @remove="removeFile" />
                </template>
            </Card>

            <div class="flex justify-end gap-2">
                <Link :href="route('contents.index')">
                    <Button type="button" label="Cancelar" outlined severity="secondary" />
                </Link>
                <Button type="submit" :loading="form.processing" label="Atualizar conteúdo" icon="pi pi-save" />
            </div>
        </form>
    </div>
</template>
