<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFormSection from '@/Components/ui/BoFormSection.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';

defineOptions({ layout: AppLayout });

defineProps({
    ideaTypes: Array,
    ideaCategories: Array,
});

const form = useForm({
    title: '',
    description: '',
    idea_type_id: null,
    idea_category_id: null,
    status: 'pending',
    references: [],
});

const submit = () => form.post(route('ideas.store'));

const addReference = () => {
    form.references.push({ title: '', description: '', url: '' });
};

const removeReference = (index) => {
    form.references.splice(index, 1);
};
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Nova ideia" subtitle="Cadastre uma nova ideia com metadados e referencias">
            <template #actions>
                <Link :href="route('ideas.index')">
                    <Button label="Voltar" outlined severity="secondary" icon="pi pi-arrow-left" />
                </Link>
            </template>
        </BoPageHeader>

        <form class="space-y-4" @submit.prevent="submit">
            <BoFormSection title="Detalhes da ideia" description="Campos principais para classificacao">
                <div class="md:col-span-2 space-y-2">
                    <label for="idea-title">Titulo</label>
                    <InputText id="idea-title" v-model="form.title" fluid :invalid="!!form.errors.title" aria-describedby="idea-title-error" />
                    <Message v-if="form.errors.title" id="idea-title-error" severity="error" size="small" variant="simple">{{ form.errors.title }}</Message>
                </div>

                <div class="md:col-span-2 space-y-2">
                    <label for="idea-description">Descricao</label>
                    <Textarea id="idea-description" v-model="form.description" rows="4" fluid :invalid="!!form.errors.description" />
                </div>

                <div class="space-y-2">
                    <label for="idea-status">Status</label>
                    <Select
                        id="idea-status"
                        v-model="form.status"
                        :options="['pending', 'maturing', 'cancelled', 'in_production', 'executed']"
                        :invalid="!!form.errors.status"
                    />
                    <Message v-if="form.errors.status" severity="error" size="small" variant="simple">{{ form.errors.status }}</Message>
                </div>

                <div class="space-y-2">
                    <label for="idea-type">Tipo</label>
                    <Select id="idea-type" v-model="form.idea_type_id" :options="ideaTypes" option-label="name" option-value="id" show-clear fluid />
                </div>

                <div class="space-y-2">
                    <label for="idea-category">Categoria</label>
                    <Select id="idea-category" v-model="form.idea_category_id" :options="ideaCategories" option-label="name" option-value="id" show-clear fluid />
                </div>
            </BoFormSection>

            <Card>
                <template #title>Referencias externas</template>
                <template #content>
                    <div class="space-y-3">
                        <div v-for="(reference, index) in form.references" :key="index" class="grid gap-3 rounded-xl border border-slate-200/80 p-3 md:grid-cols-3 dark:border-slate-800">
                            <InputText v-model="reference.title" placeholder="Titulo" />
                            <InputText v-model="reference.url" placeholder="https://..." />
                            <div class="flex gap-2">
                                <InputText v-model="reference.description" class="w-full" placeholder="Descricao" />
                                <Button type="button" icon="pi pi-trash" severity="danger" text aria-label="Remover referencia" @click="removeReference(index)" />
                            </div>
                        </div>

                        <Button type="button" icon="pi pi-plus" label="Adicionar referencia" outlined @click="addReference" />
                    </div>
                </template>
            </Card>

            <div class="flex justify-end gap-2">
                <Link :href="route('ideas.index')">
                    <Button type="button" label="Cancelar" outlined severity="secondary" />
                </Link>
                <Button type="submit" :loading="form.processing" label="Salvar ideia" icon="pi pi-save" />
            </div>
        </form>
    </div>
</template>
