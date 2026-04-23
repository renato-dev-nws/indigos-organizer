<script setup>
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { computed, ref } from 'vue';
import {
    composePhoneWithCountryCode,
    formatBrazilPhoneInput,
    normalizeCountryCodeInput,
    splitPhoneByCountryCode,
} from '@/Utils/phone';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    mustVerifyEmail: {
        type: Boolean,
        default: false,
    },
    status: {
        type: String,
        default: null,
    },
    updateRouteName: {
        type: String,
        required: true,
    },
    updateRouteParams: {
        type: [Object, Array, String, Number],
        default: null,
    },
    passwordRouteName: {
        type: String,
        required: true,
    },
    passwordRouteParams: {
        type: [Object, Array, String, Number],
        default: null,
    },
    title: {
        type: String,
        default: 'Perfil',
    },
    showEmailVerification: {
        type: Boolean,
        default: false,
    },
    canEditAdmin: {
        type: Boolean,
        default: false,
    },
});

const avatarInput = ref(null);
const avatarPreview = ref(props.user.avatar_url || '');
const showPasswordModal = ref(false);

const { countryCode: initialWaCountryCode, localDigits: initialWaLocalDigits } = splitPhoneByCountryCode(props.user.whatsapp_phone || '');
const whatsappPhoneCountryCode = ref(initialWaCountryCode || '55');

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    avatar_url: props.user.avatar_url || '',
    avatar: null,
    push_enabled: props.user.push_enabled ?? true,
    email_enabled: props.user.email_enabled ?? true,
    whatsapp_enabled: props.user.whatsapp_enabled ?? false,
    whatsapp_phone: formatBrazilPhoneInput(initialWaLocalDigits) || '',
    is_admin: !!props.user.is_admin,
});

const passwordForm = useForm({
    password: '',
    password_confirmation: '',
});

const requiresEmailVerification = computed(() => {
    return props.showEmailVerification && props.mustVerifyEmail && props.user.email_verified_at === null;
});

const canToggleAdminRole = computed(() => props.canEditAdmin && !props.user.is_super_admin);

const resolveRoute = (name, params) => {
    if (params === null || typeof params === 'undefined') {
        return route(name);
    }

    return route(name, params);
};

const isValidHttpUrl = (value) => {
    if (!value || typeof value !== 'string') {
        return false;
    }

    if (value.startsWith('/storage/')) {
        return true;
    }

    try {
        const url = new URL(value);
        return url.protocol === 'http:' || url.protocol === 'https:';
    } catch {
        return false;
    }
};

const updateAvatarPreviewFromUrl = () => {
    avatarPreview.value = isValidHttpUrl(form.avatar_url) ? form.avatar_url : '';
};

const onAvatarUrlPaste = () => {
    requestAnimationFrame(updateAvatarPreviewFromUrl);
};

const updateWhatsappPhone = (value) => {
    form.whatsapp_phone = formatBrazilPhoneInput(value);
};

const updateWhatsappPhoneCountryCode = (value) => {
    whatsappPhoneCountryCode.value = normalizeCountryCodeInput(value);
};

const onAvatarSelected = (event) => {
    const file = event.target?.files?.[0];
    if (!file) {
        return;
    }

    form.avatar = file;
    avatarPreview.value = URL.createObjectURL(file);
};

const openPasswordModal = () => {
    passwordForm.reset();
    passwordForm.clearErrors();
    showPasswordModal.value = true;
};

const goBack = () => {
    if (typeof window !== 'undefined' && window.history.length > 1) {
        window.history.back();
    }
};

const submitProfile = () => {
    form
        .transform((data) => {
            const payload = {
                ...data,
                _method: 'patch',
                push_enabled: data.push_enabled ? 1 : 0,
                email_enabled: data.email_enabled ? 1 : 0,
                whatsapp_enabled: data.whatsapp_enabled ? 1 : 0,
                whatsapp_phone: composePhoneWithCountryCode(whatsappPhoneCountryCode.value, data.whatsapp_phone) || null,
            };

            if (canToggleAdminRole.value) {
                payload.is_admin = data.is_admin ? 1 : 0;
            } else {
                delete payload.is_admin;
            }

            return payload;
        })
        .post(resolveRoute(props.updateRouteName, props.updateRouteParams), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: (page) => {
                const updatedUser = page?.props?.user ?? page?.props?.auth?.user ?? null;

                if (updatedUser) {
                    form.name = updatedUser.name ?? form.name;
                    form.email = updatedUser.email ?? form.email;
                    form.avatar_url = updatedUser.avatar_url || '';
                    form.push_enabled = updatedUser.push_enabled ?? form.push_enabled;
                    form.email_enabled = updatedUser.email_enabled ?? form.email_enabled;
                    form.whatsapp_enabled = updatedUser.whatsapp_enabled ?? form.whatsapp_enabled;
                    form.is_admin = !!updatedUser.is_admin;

                    const { countryCode: newWaCountryCode, localDigits: newWaLocalDigits } = splitPhoneByCountryCode(updatedUser.whatsapp_phone || '');
                    whatsappPhoneCountryCode.value = newWaCountryCode || '55';
                    form.whatsapp_phone = formatBrazilPhoneInput(newWaLocalDigits) || '';
                }

                form.avatar = null;
                if (avatarInput.value) {
                    avatarInput.value.value = '';
                }

                updateAvatarPreviewFromUrl();
            },
        });
};

const updatePassword = () => {
    passwordForm.put(resolveRoute(props.passwordRouteName, props.passwordRouteParams), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            showPasswordModal.value = false;
        },
    });
};
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader :title="title">
            <template #actions>
                <Button class="!hidden md:!inline-flex" label="Voltar" outlined severity="secondary" icon="pi pi-arrow-left" @click="goBack" />
                <Button class="!inline-flex md:!hidden" icon="pi pi-arrow-left" rounded outlined severity="secondary" aria-label="Voltar" @click="goBack" />
            </template>
        </BoPageHeader>

        <form class="space-y-6" @submit.prevent="submitProfile">
            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70 dark:bg-slate-900 dark:ring-slate-800">
                    <div class="space-y-6">
                        <div>
                            <InputLabel for="name" value="Nome" class="text-slate-700 dark:text-slate-200" />

                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full border-slate-300 bg-white text-slate-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                                required
                                autofocus
                                autocomplete="name"
                            />

                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <div>
                            <InputLabel for="email" value="E-mail" class="text-slate-700 dark:text-slate-200" />

                            <TextInput
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="mt-1 block w-full border-slate-300 bg-white text-slate-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                                required
                                autocomplete="username"
                            />

                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_220px]">
                            <div>
                                <InputLabel for="avatar_url" value="Avatar URL" class="text-slate-700 dark:text-slate-200" />

                                <TextInput
                                    id="avatar_url"
                                    v-model="form.avatar_url"
                                    type="url"
                                    class="mt-1 block w-full border-slate-300 bg-white text-slate-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                                    autocomplete="photo"
                                    placeholder="https://..."
                                    @blur="updateAvatarPreviewFromUrl"
                                    @paste="onAvatarUrlPaste"
                                />

                                <input ref="avatarInput" type="file" accept="image/*" class="hidden" @change="onAvatarSelected" />

                                <div class="mt-2 flex flex-wrap gap-2">
                                    <Button
                                        type="button"
                                        label="Enviar imagem"
                                        icon="pi pi-upload"
                                        outlined
                                        severity="secondary"
                                        @click="avatarInput?.click()"
                                    />
                                    <Button
                                        type="button"
                                        label="Atualizar previa"
                                        icon="pi pi-refresh"
                                        outlined
                                        severity="secondary"
                                        @click="updateAvatarPreviewFromUrl"
                                    />
                                </div>

                                <InputError class="mt-2" :message="form.errors.avatar_url" />
                                <InputError class="mt-2" :message="form.errors.avatar" />
                            </div>

                            <div>
                                <InputLabel value="Previa" class="text-slate-700 dark:text-slate-200" />
                                <div class="mt-1 flex h-[120px] w-full items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                                    <img v-if="avatarPreview" :src="avatarPreview" alt="Previa do avatar" class="h-full w-full object-cover" />
                                    <Icon v-else icon="mdi:person" class="h-9 w-9 text-slate-400 dark:text-slate-500" />
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-3 dark:border-slate-700 dark:bg-slate-900/50">
                            <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Seguranca</h3>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">A alteracao de senha e feita em um modal com confirmacao.</p>
                            <Button type="button" class="mt-3" label="Alterar Senha" icon="pi pi-lock" @click="openPasswordModal" />
                        </div>

                        <div v-if="requiresEmailVerification">
                            <p class="text-sm text-slate-700 dark:text-slate-200">
                                Seu e-mail ainda nao foi verificado.
                                <Link
                                    :href="route('verification.send')"
                                    method="post"
                                    as="button"
                                    class="rounded-md text-sm text-slate-600 underline hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-slate-300 dark:hover:text-slate-100 dark:focus:ring-offset-slate-900"
                                >
                                    Clique aqui para reenviar o e-mail de verificacao.
                                </Link>
                            </p>

                            <div v-show="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                Um novo link de verificacao foi enviado para o seu e-mail.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70 dark:bg-slate-900 dark:ring-slate-800">
                    <div class="space-y-3">
                        <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Preferencias de notificacoes</h3>

                        <label class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-700">
                            <span class="text-sm text-slate-700 dark:text-slate-200">Push Notifications</span>
                            <Checkbox v-model="form.push_enabled" input-id="push_enabled" binary />
                        </label>

                        <label class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-700">
                            <span class="text-sm text-slate-700 dark:text-slate-200">Notificacoes por e-mail</span>
                            <Checkbox v-model="form.email_enabled" input-id="email_enabled" binary />
                        </label>

                        <label class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-700">
                            <span class="text-sm text-slate-700 dark:text-slate-200">Notificacoes por WhatsApp</span>
                            <Checkbox v-model="form.whatsapp_enabled" input-id="whatsapp_enabled" binary />
                        </label>

                        <div class="space-y-2">
                            <InputLabel value="Número WhatsApp" class="text-slate-700 dark:text-slate-200" />
                            <InputGroup>
                                <InputGroupAddon>+</InputGroupAddon>
                                <InputText
                                    :model-value="whatsappPhoneCountryCode"
                                    style="width: 54px; min-width: 54px; max-width: 54px"
                                    inputmode="numeric"
                                    @update:model-value="updateWhatsappPhoneCountryCode"
                                />
                                <InputText
                                    :model-value="form.whatsapp_phone"
                                    placeholder="(11) 98765-4321"
                                    fluid
                                    @update:model-value="updateWhatsappPhone"
                                />
                            </InputGroup>
                            <small class="text-slate-500 dark:text-slate-400">Número utilizado para envio de notificações via WhatsApp.</small>
                            <InputError :message="form.errors.whatsapp_phone" />
                        </div>

                        <div v-if="canToggleAdminRole" class="rounded-lg border border-slate-200 px-3 py-3 dark:border-slate-700">
                            <label class="flex items-center justify-between">
                                <span class="text-sm text-slate-700 dark:text-slate-200">Usuario administrador</span>
                                <Checkbox v-model="form.is_admin" input-id="is_admin" binary />
                            </label>
                            <InputError class="mt-2" :message="form.errors.is_admin" />
                        </div>

                        <InputError class="mt-1" :message="form.errors.push_enabled" />
                        <InputError class="mt-1" :message="form.errors.email_enabled" />
                        <InputError class="mt-1" :message="form.errors.whatsapp_enabled" />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-4">
                <Button type="submit" label="Salvar alteracoes" icon="pi pi-save" :loading="form.processing" />
            </div>
        </form>

        <Dialog v-model:visible="showPasswordModal" modal :style="{ width: '420px', maxWidth: '95vw' }" header="Alterar Senha">
            <form class="space-y-4" @submit.prevent="updatePassword">
                <div>
                    <InputLabel for="new_password" value="Nova senha" class="text-slate-700 dark:text-slate-200" />
                    <TextInput
                        id="new_password"
                        v-model="passwordForm.password"
                        type="password"
                        class="mt-1 block w-full border-slate-300 bg-white text-slate-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                        autocomplete="new-password"
                    />
                    <InputError :message="passwordForm.errors.password" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="new_password_confirmation" value="Confirmar nova senha" class="text-slate-700 dark:text-slate-200" />
                    <TextInput
                        id="new_password_confirmation"
                        v-model="passwordForm.password_confirmation"
                        type="password"
                        class="mt-1 block w-full border-slate-300 bg-white text-slate-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                        autocomplete="new-password"
                    />
                    <InputError :message="passwordForm.errors.password_confirmation" class="mt-2" />
                </div>

                <div class="flex justify-end gap-2 pt-1">
                    <Button type="button" label="Cancelar" outlined severity="secondary" @click="showPasswordModal = false" />
                    <Button type="submit" label="Salvar senha" icon="pi pi-check" :loading="passwordForm.processing" />
                </div>
            </form>
        </Dialog>
    </div>
</template>
