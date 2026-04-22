<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { ref } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;
const avatarInput = ref(null);
const avatarPreview = ref(user.avatar_url || '');

const form = useForm({
    name: user.name,
    email: user.email,
    avatar_url: user.avatar_url || '',
    avatar: null,
    push_enabled: user.push_enabled ?? true,
    email_enabled: user.email_enabled ?? true,
    whatsapp_enabled: user.whatsapp_enabled ?? false,
});

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

const onAvatarSelected = (event) => {
    const file = event.target?.files?.[0];
    if (!file) {
        return;
    }

    form.avatar = file;
    avatarPreview.value = URL.createObjectURL(file);
};

const submitProfile = () => {
    form
        .transform((data) => ({
            ...data,
            _method: 'patch',
        }))
        .post(route('profile.update'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.avatar = null;
                if (avatarInput.value) {
                    avatarInput.value.value = '';
                }

                updateAvatarPreviewFromUrl();
            },
        });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Profile Information
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Update your account's profile information and email address.
            </p>
        </header>

        <form @submit.prevent="submitProfile" class="mt-6 space-y-6">
            <div>
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_220px]">
                <div>
                    <InputLabel for="avatar_url" value="Avatar URL" />

                    <TextInput
                        id="avatar_url"
                        type="url"
                        class="mt-1 block w-full"
                        v-model="form.avatar_url"
                        autocomplete="photo"
                        placeholder="https://..."
                        @blur="updateAvatarPreviewFromUrl"
                        @paste="onAvatarUrlPaste"
                    />

                    <input ref="avatarInput" type="file" accept="image/*" class="hidden" @change="onAvatarSelected" />

                    <div class="mt-2 flex flex-wrap gap-2">
                        <PrimaryButton type="button" @click="avatarInput?.click()">Enviar imagem</PrimaryButton>
                        <button
                            type="button"
                            class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                            @click="updateAvatarPreviewFromUrl"
                        >
                            Atualizar prévia
                        </button>
                    </div>

                    <InputError class="mt-2" :message="form.errors.avatar_url" />
                    <InputError class="mt-2" :message="form.errors.avatar" />
                </div>

                <div>
                    <InputLabel value="Prévia" />
                    <div class="mt-1 flex h-[120px] w-full items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                        <img v-if="avatarPreview" :src="avatarPreview" alt="Prévia do avatar" class="h-full w-full object-cover" />
                        <Icon v-else icon="mdi:person" class="h-9 w-9 text-slate-400 dark:text-slate-500" />
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Preferências de notificações</h3>

                <label class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-700">
                    <span class="text-sm text-slate-700 dark:text-slate-200">Push Notifications</span>
                    <input v-model="form.push_enabled" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                </label>

                <label class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-700">
                    <span class="text-sm text-slate-700 dark:text-slate-200">Notificações por e-mail</span>
                    <input v-model="form.email_enabled" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                </label>

                <label class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-700">
                    <span class="text-sm text-slate-700 dark:text-slate-200">Notificações por WhatsApp</span>
                    <input v-model="form.whatsapp_enabled" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                </label>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
