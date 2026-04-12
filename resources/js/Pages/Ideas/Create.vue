<script setup>
import { computed, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFormSection from '@/Components/ui/BoFormSection.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import AppSpeechTextareaAssist from '@/Components/AppSpeechTextareaAssist.vue';

const props = defineProps({ ideaTypes: Array, ideaCategories: Array, plans: Array, contents: Array, users: Array });
defineOptions({ layout: AppLayout });

const form = useForm({
    related_type: 'none',
    content_id: null,
    plan_id: null,
    plan_phase_id: null,
    title: '',
    description: '',
    idea_type_id: null,
    idea_category_id: null,
    status: 'in_drawer',
    is_private: false,
    voter_users: [],
    references: [],
});

const selectedPlan = computed(() => props.plans?.find((plan) => plan.id === form.plan_id));
const phaseOptions = computed(() => selectedPlan.value?.phases ?? []);

watch(() => form.related_type, () => {
    form.content_id = null;
    form.plan_id = null;
    form.plan_phase_id = null;
});
watch(() => form.plan_id, () => { form.plan_phase_id = null; });
watch(() => form.status, (newStatus) => {
    if (!['in_drawer', 'trash'].includes(newStatus)) form.is_private = false;
    if (newStatus !== 'on_board') form.voter_users = [];
});

const submit = () => form.post(route('ideas.store'));
const addReference = () => form.references.push({ title: '', description: '', url: '' });
const removeReference = (index) => form.references.splice(index, 1);
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Nova ideia" subtitle="Cadastre ideias relacionadas ao conteúdo, plano ou gestão da banda">
            <template #actions>
                <Link :href="route('ideas.index')">
                    <Button label="Voltar" outlined severity="secondary" icon="pi pi-arrow-left" />
                </Link>
            </template>
        </BoPageHeader>

        <form class="space-y-4" @submit.prevent="submit">
            <BoFormSection title="Detalhes da ideia" description="Fluxo de relacionamento, classificação e votação">
                <div class="space-y-2">
                    <label>Relacionada a</label>
                    <Select
                        v-model="form.related_type"
                        :options="[
                            { label: 'Conteúdo novo', value: 'new_content' },
                            { label: 'Plano novo', value: 'new_plan' },
                            { label: 'Conteúdo existente', value: 'existing_content' },
                            { label: 'Plano existente', value: 'existing_plan' },
                            { label: 'Administrativa', value: 'administrative' },
                            { label: 'Nenhuma', value: 'none' },
                        ]"
                        option-label="label"
                        option-value="value"
                        fluid
                    />
                </div>

                <div v-if="form.related_type === 'existing_content'" class="space-y-2">
                    <label>Conteúdo</label>
                    <Select v-model="form.content_id" :options="contents" option-label="title" option-value="id" show-clear fluid />
                </div>

                <div v-if="form.related_type === 'existing_plan'" class="space-y-2">
                    <label>Plano</label>
                    <Select v-model="form.plan_id" :options="plans" option-label="title" option-value="id" show-clear fluid />
                </div>

                <div v-if="form.related_type === 'existing_plan' && form.plan_id" class="space-y-2">
                    <label>Fase do plano (opcional)</label>
                    <Select v-model="form.plan_phase_id" :options="phaseOptions" option-label="title" option-value="id" show-clear fluid />
                </div>

                <div class="md:col-span-2 space-y-2">
                    <label>Título</label>
                    <InputText v-model="form.title" fluid :invalid="!!form.errors.title" />
                </div>

                <div class="md:col-span-2 space-y-2">
                    <label>Descrição</label>
                    <AppSpeechTextareaAssist v-model="form.description" />
                    <Textarea v-model="form.description" rows="4" fluid />
                </div>

                <div class="space-y-2">
                    <label>Tipo</label>
                    <Select v-model="form.idea_type_id" :options="ideaTypes" option-label="name" option-value="id" show-clear fluid />
                </div>

                <div class="space-y-2">
                    <label>Categoria</label>
                    <Select v-model="form.idea_category_id" :options="ideaCategories" option-label="name" option-value="id" show-clear fluid />
                </div>

                <div class="space-y-2">
                    <label>Status</label>
                    <Select
                        v-model="form.status"
                        :options="[
                            { label: 'Na gaveta', value: 'in_drawer' },
                            { label: 'Na mesa', value: 'on_table' },
                            { label: 'No quadro', value: 'on_board' },
                            { label: 'Em execução', value: 'executing' },
                            { label: 'Executada', value: 'executed' },
                            { label: 'No lixo', value: 'trash' },
                        ]"
                        option-label="label"
                        option-value="value"
                        fluid
                    />
                </div>

                <div v-if="['in_drawer', 'trash'].includes(form.status)" class="space-y-2">
                    <label>Ideia privada</label>
                    <div class="flex items-center gap-2">
                        <ToggleSwitch v-model="form.is_private" />
                        <span class="text-sm text-slate-500">Permitido quando o status for Na gaveta ou No lixo.</span>
                    </div>
                </div>

                <div v-if="form.status === 'on_board'" class="md:col-span-2 space-y-2">
                    <label>Usuários que podem votar</label>
                    <MultiSelect v-model="form.voter_users" :options="users" option-label="name" option-value="id" display="chip" fluid />
                </div>
            </BoFormSection>

            <Card>
                <template #title>Referências</template>
                <template #content>
                    <div class="space-y-3">
                        <div v-for="(reference, index) in form.references" :key="index" class="grid gap-3 md:grid-cols-3">
                            <InputText v-model="reference.title" placeholder="Título" />
                            <InputText v-model="reference.url" placeholder="https://..." />
                            <div class="flex gap-2">
                                <InputText v-model="reference.description" class="w-full" placeholder="Descrição" />
                                <Button type="button" icon="pi pi-trash" text severity="danger" @click="removeReference(index)" />
                            </div>
                        </div>
                        <Button type="button" icon="pi pi-plus" label="Adicionar referência" outlined @click="addReference" />
                    </div>
                </template>
            </Card>

            <div class="flex justify-end gap-2">
                <Link :href="route('ideas.index')"><Button type="button" label="Cancelar" outlined severity="secondary" /></Link>
                <Button type="submit" :loading="form.processing" label="Salvar ideia" icon="pi pi-save" />
            </div>
        </form>
    </div>
</template>
