<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import { Icon } from '@iconify/vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    logoUrl: { type: String, default: null },
    iconUrl: { type: String, default: null },
});

// Logo
const logoInput = ref(null);
const logoPreview = ref(props.logoUrl);

const logoForm = useForm({ logo: null });
const removeLogo = useForm({ remove_logo: true });

const onLogoSelected = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    logoPreview.value = URL.createObjectURL(file);
    logoForm.logo = file;
    logoForm.post(route('settings.system.update'), {
        forceFormData: true,
        onSuccess: () => { logoInput.value.value = ''; },
    });
};

const handleRemoveLogo = () => {
    removeLogo.post(route('settings.system.update'), {
        onSuccess: () => { logoPreview.value = null; },
    });
};

// Icon
const iconInput = ref(null);
const iconPreview = ref(props.iconUrl);

const iconForm = useForm({ icon: null });
const removeIcon = useForm({ remove_icon: true });

const onIconSelected = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    iconPreview.value = URL.createObjectURL(file);
    iconForm.icon = file;
    iconForm.post(route('settings.system.update'), {
        forceFormData: true,
        onSuccess: () => { iconInput.value.value = ''; },
    });
};

const handleRemoveIcon = () => {
    removeIcon.post(route('settings.system.update'), {
        onSuccess: () => { iconPreview.value = null; },
    });
};
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Sistema" subtitle="Personalize a identidade visual da aplicação." />

        <div class="grid gap-6 md:grid-cols-2">
            <!-- Logo -->
            <div class="space-y-4 rounded-2xl bg-white/80 p-5 shadow-sm ring-1 ring-slate-200/70 dark:bg-slate-900/70 dark:ring-slate-800">
                <div>
                    <h2 class="font-semibold text-slate-800 dark:text-slate-100">Logotipo</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                        Aparece na tela de login e na barra lateral expandida. Formatos: PNG, JPG, SVG, WebP (máx. 2 MB).
                    </p>
                </div>

                <!-- Preview -->
                <div class="flex h-24 items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800">
                    <img
                        v-if="logoPreview"
                        :src="logoPreview"
                        alt="Logo preview"
                        class="max-h-20 max-w-full object-contain"
                    />
                    <span v-else class="text-sm text-slate-400">Sem logotipo</span>
                </div>

                <!-- Actions -->
                <input ref="logoInput" type="file" accept="image/*" class="hidden" @change="onLogoSelected" />
                <div class="flex gap-2">
                    <Button
                        type="button"
                        size="small"
                        :loading="logoForm.processing"
                        @click="logoInput.click()"
                    >
                        <Icon icon="ph:upload-simple-bold" class="mr-1 h-4 w-4" />
                        Selecionar
                    </Button>
                    <Button
                        v-if="logoPreview"
                        type="button"
                        size="small"
                        severity="danger"
                        outlined
                        :loading="removeLogo.processing"
                        @click="handleRemoveLogo"
                    >
                        <Icon icon="ph:trash-bold" class="mr-1 h-4 w-4" />
                        Remover
                    </Button>
                </div>
            </div>

            <!-- Icon -->
            <div class="space-y-4 rounded-2xl bg-white/80 p-5 shadow-sm ring-1 ring-slate-200/70 dark:bg-slate-900/70 dark:ring-slate-800">
                <div>
                    <h2 class="font-semibold text-slate-800 dark:text-slate-100">Ícone</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                        Aparece como ícone no navegador, na barra lateral recolhida e no topo em mobile. Formatos: PNG, SVG, ICO, WebP (máx. 512 KB). Quadrado (512×512 recomendado).
                    </p>
                </div>

                <!-- Preview -->
                <div class="flex h-24 items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800">
                    <img
                        v-if="iconPreview"
                        :src="iconPreview"
                        alt="Icon preview"
                        class="h-16 w-16 rounded-xl object-contain"
                    />
                    <span v-else class="text-sm text-slate-400">Sem ícone</span>
                </div>

                <!-- Actions -->
                <input ref="iconInput" type="file" accept="image/*" class="hidden" @change="onIconSelected" />
                <div class="flex gap-2">
                    <Button
                        type="button"
                        size="small"
                        :loading="iconForm.processing"
                        @click="iconInput.click()"
                    >
                        <Icon icon="ph:upload-simple-bold" class="mr-1 h-4 w-4" />
                        Selecionar
                    </Button>
                    <Button
                        v-if="iconPreview"
                        type="button"
                        size="small"
                        severity="danger"
                        outlined
                        :loading="removeIcon.processing"
                        @click="handleRemoveIcon"
                    >
                        <Icon icon="ph:trash-bold" class="mr-1 h-4 w-4" />
                        Remover
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
