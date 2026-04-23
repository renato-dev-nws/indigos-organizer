<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFormSection from '@/Components/ui/BoFormSection.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import {
    composePhoneWithCountryCode,
    formatBrazilPhoneInput,
    normalizeCountryCodeInput,
} from '@/Utils/phone';

defineOptions({ layout: AppLayout });

const goBack = () => {
    if (typeof window !== 'undefined' && window.history.length > 1) {
        window.history.back();
    }
};

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    avatar_url: '',
    is_admin: false,
    theme: 'system',
    whatsapp_phone: '',
});

const whatsappCountryCode = ref('55');

const page = usePage();
const isAdmin = !!page.props.auth?.user?.is_admin;

const updateWhatsappPhone = (value) => {
    form.whatsapp_phone = formatBrazilPhoneInput(value);
};

const updateWhatsappCountryCode = (value) => {
    whatsappCountryCode.value = normalizeCountryCodeInput(value);
};

const submit = () => form
    .transform((data) => ({
        ...data,
        whatsapp_phone: composePhoneWithCountryCode(whatsappCountryCode.value, data.whatsapp_phone) || null,
    }))
    .post(route('users.store'));
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Novo usuário" subtitle="Cadastro de usuários da equipe">
            <template #actions>
                <Button class="!hidden md:!inline-flex" label="Voltar" outlined severity="secondary" icon="pi pi-arrow-left" @click="goBack" />
                <Button class="!inline-flex md:!hidden" icon="pi pi-arrow-left" rounded outlined severity="secondary" aria-label="Voltar" @click="goBack" />
            </template>
        </BoPageHeader>

        <form class="space-y-4" @submit.prevent="submit">
            <BoFormSection title="Dados do usuário" description="Informações de acesso e preferências">
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
                    <label for="user-password">Senha</label>
                    <Password id="user-password" v-model="form.password" :feedback="false" toggle-mask fluid :invalid="!!form.errors.password" />
                    <Message v-if="form.errors.password" severity="error" size="small" variant="simple">{{ form.errors.password }}</Message>
                </div>

                <div class="space-y-2">
                    <label for="user-password-confirmation">Confirmar senha</label>
                    <Password id="user-password-confirmation" v-model="form.password_confirmation" :feedback="false" toggle-mask fluid />
                </div>

                <div class="space-y-2">
                    <label for="user-whatsapp">WhatsApp</label>
                    <InputGroup>
                        <InputGroupAddon>+</InputGroupAddon>
                        <InputText
                            :model-value="whatsappCountryCode"
                            style="width: 54px; min-width: 54px; max-width: 54px"
                            inputmode="numeric"
                            @update:model-value="updateWhatsappCountryCode"
                        />
                        <InputText id="user-whatsapp" :model-value="form.whatsapp_phone" placeholder="(11) 98765-4321" fluid @update:model-value="updateWhatsappPhone" />
                    </InputGroup>
                    <small class="text-slate-500 dark:text-slate-400">Número utilizado para envio de notificações via WhatsApp.</small>
                    <Message v-if="form.errors.whatsapp_phone" severity="error" size="small" variant="simple">{{ form.errors.whatsapp_phone }}</Message>
                </div>
            </BoFormSection>

            <div class="flex justify-end gap-2">
                <Link :href="route('users.index')">
                    <Button type="button" label="Cancelar" outlined severity="secondary" />
                </Link>
                <Button type="submit" :loading="form.processing" label="Salvar usuário" icon="pi pi-save" />
            </div>
        </form>
    </div>
</template>
