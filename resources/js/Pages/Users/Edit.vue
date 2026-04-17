<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFormSection from '@/Components/ui/BoFormSection.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';

defineOptions({ layout: AppLayout });

const goBack = () => {
    if (typeof window !== 'undefined' && window.history.length > 1) {
        window.history.back();
    }
};

const props = defineProps({ user: Object });

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
    avatar_url: props.user.avatar_url ?? '',
    is_admin: !!props.user.is_admin,
    theme: props.user.theme || 'system',
});

const page = usePage();
const isAdmin = !!page.props.auth?.user?.is_admin;

const submit = () => form.put(route('users.update', props.user.id));
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Editar usuário" subtitle="Atualize os dados do usuário">
            <template #actions>
                <Button class="!hidden md:!inline-flex" label="Voltar" outlined severity="secondary" icon="pi pi-arrow-left" @click="goBack" />
                <Button class="!inline-flex md:!hidden" icon="pi pi-arrow-left" rounded outlined severity="secondary" aria-label="Voltar" @click="goBack" />
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

                <div class="space-y-2 md:col-span-2">
                    <label for="user-avatar">Avatar (URL)</label>
                    <InputText id="user-avatar" v-model="form.avatar_url" fluid :invalid="!!form.errors.avatar_url" placeholder="https://..." />
                    <Message v-if="form.errors.avatar_url" severity="error" size="small" variant="simple">{{ form.errors.avatar_url }}</Message>
                </div>

                <div v-if="isAdmin" class="space-y-2 md:col-span-2">
                    <label for="user-admin">Permissão</label>
                    <div class="flex items-center gap-2">
                        <Checkbox id="user-admin" v-model="form.is_admin" binary />
                        <label for="user-admin" class="text-sm">Usuário administrador</label>
                    </div>
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
