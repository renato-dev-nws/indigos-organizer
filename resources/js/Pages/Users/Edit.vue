<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFormSection from '@/Components/ui/BoFormSection.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({ user: Object });

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
    theme: props.user.theme || 'system',
});

const submit = () => form.put(route('users.update', props.user.id));
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Editar usuário" subtitle="Atualize os dados do usuário">
            <template #actions>
                <Link :href="route('users.index')">
                    <Button label="Voltar" outlined severity="secondary" icon="pi pi-arrow-left" />
                </Link>
            </template>
        </BoPageHeader>

        <form class="space-y-4" @submit.prevent="submit">
            <BoFormSection title="Dados do usuário" description="Deixe a senha em branco para manter a atual">
                <div class="space-y-2">
                    <label for="user-name">Nome</label>
                    <InputText id="user-name" v-model="form.name" fluid :invalid="!!form.errors.name" />
                    <Message v-if="form.errors.name" severity="error" size="small" variant="simple">{{ form.errors.name }}</Message>
                </div>

                <div class="space-y-2">
                    <label for="user-email">E-mail</label>
                    <InputText id="user-email" v-model="form.email" fluid :invalid="!!form.errors.email" />
                    <Message v-if="form.errors.email" severity="error" size="small" variant="simple">{{ form.errors.email }}</Message>
                </div>

                <div class="space-y-2">
                    <label for="user-theme">Tema padrão</label>
                    <Select id="user-theme" v-model="form.theme" :options="['light', 'dark', 'system']" fluid />
                </div>

                <div class="space-y-2">
                    <label for="user-password">Nova senha</label>
                    <Password id="user-password" v-model="form.password" :feedback="false" toggle-mask fluid :invalid="!!form.errors.password" />
                    <Message v-if="form.errors.password" severity="error" size="small" variant="simple">{{ form.errors.password }}</Message>
                </div>

                <div class="space-y-2">
                    <label for="user-password-confirmation">Confirmar nova senha</label>
                    <Password id="user-password-confirmation" v-model="form.password_confirmation" :feedback="false" toggle-mask fluid />
                </div>
            </BoFormSection>

            <div class="flex justify-end gap-2">
                <Link :href="route('users.index')">
                    <Button type="button" label="Cancelar" outlined severity="secondary" />
                </Link>
                <Button type="submit" :loading="form.processing" label="Salvar alterações" icon="pi pi-save" />
            </div>
        </form>
    </div>
</template>
