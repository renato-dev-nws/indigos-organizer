<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFormSection from '@/Components/ui/BoFormSection.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import AppRichText from '@/Components/AppRichText.vue';
import AppSpeechTextareaAssist from '@/Components/AppSpeechTextareaAssist.vue';

defineOptions({ layout: AppLayout });

const goBack = () => {
    if (typeof window !== 'undefined' && window.history.length > 1) {
        window.history.back();
    }
};

defineProps({
    platforms: Array,
    types: Array,
    categories: Array,
    styles: Array,
    ideas: Array,
});

const form = useForm({
    idea_id: null,
    title: '',
    script: '',
    content_platform_ids: [],
    venue_style_ids: [],
    idea_type_id: null,
    idea_category_id: null,
    status: 'queued',
    links: [],
    planned_publish_at: '',
    published_at: '',
});

const submit = () => form.post(route('contents.store'));

const addLink = () => {
    form.links.push({ title: '', url: '' });
};

const removeLink = (index) => {
    form.links.splice(index, 1);
};
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Novo conteúdo" supratitle="CONTEÚDOS" subtitle="" icon="mdi:add-circle-outline">
            <template #actions>
                <div>
                    <Button class="!hidden md:!inline-flex" label="Voltar" outlined severity="secondary" icon="pi pi-arrow-left" @click="goBack" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-arrow-left" rounded outlined severity="secondary" aria-label="Voltar" @click="goBack" />
                </div>
            </template>
        </BoPageHeader>

        <form class="space-y-4" @submit.prevent="submit">
            <BoFormSection title="Dados principais" description="Informações de planejamento e classificação">
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
                            { label: 'Finalizado', value: 'finalized' },
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
                    <label for="content-platforms">Plataformas</label>
                    <MultiSelect
                        id="content-platforms"
                        v-model="form.content_platform_ids"
                        :options="platforms"
                        option-label="name"
                        option-value="id"
                        placeholder="Selecionar plataformas"
                        fluid
                    />
                </div>

                <div class="space-y-2">
                    <label for="content-type">Tipo</label>
                    <Select id="content-type" v-model="form.idea_type_id" :options="types" option-label="name" option-value="id" show-clear fluid />
                </div>

                <div class="space-y-2">
                    <label for="content-category">Categoria</label>
                    <Select id="content-category" v-model="form.idea_category_id" :options="categories" option-label="name" option-value="id" show-clear fluid />
                </div>

                <div class="md:col-span-2 space-y-2">
                    <label for="content-styles">Estilos</label>
                    <MultiSelect id="content-styles" v-model="form.venue_style_ids" :options="styles" option-label="name" option-value="id" display="chip" fluid />
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
                    <div class="flex items-center justify-between gap-2">
                        <label for="content-script">Roteiro</label>
                        <AppSpeechTextareaAssist v-model="form.script" />
                    </div>
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

            <div class="flex justify-end gap-2">
                <Link :href="route('contents.index')">
                    <Button type="button" label="Cancelar" outlined severity="secondary" />
                </Link>
                <Button type="submit" :loading="form.processing" label="Salvar conteúdo" icon="pi pi-save" />
            </div>
        </form>
    </div>
</template>
