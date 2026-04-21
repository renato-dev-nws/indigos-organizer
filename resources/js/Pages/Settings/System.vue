<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import { Icon } from '@iconify/vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    logoUrl: { type: String, default: null },
    iconUrl: { type: String, default: null },
    moduleDefinitions: { type: Array, default: () => [] },
    moduleColors: { type: Object, default: () => ({}) },
    colorPalette: { type: Array, default: () => [] },
    cloudIntegrations: { type: Object, default: () => ({}) },
});
const page = usePage();
const readOnly = !page.props.auth?.user?.is_admin;

const editingModule = ref(null);
const selectedColorToken = ref('slate-500');
const localModuleColors = ref({ ...(props.moduleColors || {}) });
const showColorModal = computed(() => editingModule.value !== null);
const moduleColorForm = useForm({ module_colors: {} });
const cloudIntegrationsLocal = ref(JSON.parse(JSON.stringify(props.cloudIntegrations || {
    google: { configured: false, base_folder: 'ERP_Arquivos' },
    dropbox: { configured: false, base_folder: 'ERP_Arquivos' },
})));

const cloudFolderForm = useForm({ base_folder: '' });

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

const openColorModal = (moduleDef) => {
    editingModule.value = moduleDef;
    selectedColorToken.value = localModuleColors.value[moduleDef.key] || moduleDef.default_color || 'slate-500';
};

const closeColorModal = () => {
    editingModule.value = null;
};

const colorHex = (token) => props.colorPalette.find((entry) => entry.token === token)?.hex || '#64748b';

const saveModuleColor = () => {
    if (!editingModule.value) {
        return;
    }

    moduleColorForm.module_colors = {
        [editingModule.value.key]: selectedColorToken.value,
    };

    moduleColorForm.post(route('settings.system.update'), {
        preserveScroll: true,
        onSuccess: () => {
            localModuleColors.value[editingModule.value.key] = selectedColorToken.value;
            closeColorModal();
        },
    });
};

const connectProvider = (provider) => {
    window.location.href = route('cloud.redirect', { provider });
};

const disconnectProvider = (provider) => {
    router.delete(route('cloud.disconnect', { provider }), { preserveScroll: true });
};

const testProvider = (provider) => {
    router.post(route('cloud.test', { provider }), {}, { preserveScroll: true });
};

const saveProviderFolder = (provider) => {
    cloudFolderForm.base_folder = cloudIntegrationsLocal.value?.[provider]?.base_folder || 'ERP_Arquivos';
    cloudFolderForm.patch(route('cloud.folder', { provider }), { preserveScroll: true });
};

const cloudStatusText = (provider) => (props.cloudIntegrations?.[provider]?.configured ? 'Conectado' : 'Não conectado');
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Sistema" subtitle="Personalize a identidade visual da aplicação." />

        <Card>
            <template #title>Cores dos módulos</template>
            <template #content>
                <div class="space-y-3">
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Defina a cor de destaque por módulo. Configurações e Usuários não aparecem nesta lista.
                    </p>

                    <div class="grid gap-3 md:grid-cols-2">
                        <div
                            v-for="moduleDef in moduleDefinitions"
                            :key="moduleDef.key"
                            class="grid grid-cols-[minmax(0,1fr)_140px] overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900"
                        >
                            <div class="flex items-center gap-2 px-3 py-3">
                                <Icon :icon="moduleDef.icon" class="h-5 w-5 text-slate-600 dark:text-slate-300" />
                                <span class="font-medium">{{ moduleDef.title }}</span>
                            </div>
                            <button
                                type="button"
                                class="flex items-center justify-center gap-2 border-l border-slate-200 px-2 py-3 text-sm font-semibold transition hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800"
                                :disabled="readOnly"
                                @click="openColorModal(moduleDef)"
                            >
                                <span class="inline-block h-4 w-4 rounded-full ring-1 ring-black/10" :style="{ backgroundColor: colorHex(localModuleColors[moduleDef.key] || moduleDef.default_color) }" />
                                {{ localModuleColors[moduleDef.key] || moduleDef.default_color }}
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </Card>

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
                        :disabled="readOnly"
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
                        :disabled="readOnly"
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
                        :disabled="readOnly"
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
                        :disabled="readOnly"
                        @click="handleRemoveIcon"
                    >
                        <Icon icon="ph:trash-bold" class="mr-1 h-4 w-4" />
                        Remover
                    </Button>
                </div>
            </div>
        </div>

        <Card>
            <template #title>Integrações de armazenamento</template>
            <template #content>
                <div class="grid gap-5 xl:grid-cols-2">
                    <div class="rounded-xl border border-slate-200/80 p-4 dark:border-slate-800">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h3 class="text-lg font-semibold">Google Drive</h3>
                            <Tag :severity="cloudIntegrations?.google?.configured ? 'success' : 'secondary'" :value="cloudStatusText('google')" />
                        </div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            {{ cloudIntegrations?.google?.account_email || 'Conecte sua conta para enviar arquivos diretamente para o Google Drive.' }}
                        </p>
                        <p class="mt-2 text-xs text-slate-400 dark:text-slate-500">
                            Callbacks autorizados no Google Cloud: {{ route('auth.google.callback') }} e {{ route('cloud.callback', { provider: 'google' }) }}.
                        </p>
                        <div class="mt-3 space-y-2">
                            <label>Pasta base</label>
                            <div class="flex gap-2">
                                <InputText v-model="cloudIntegrationsLocal.google.base_folder" :disabled="readOnly" placeholder="ERP_Arquivos" />
                                <Button type="button" icon="pi pi-save" outlined :disabled="readOnly" @click="saveProviderFolder('google')" />
                            </div>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <Button
                                v-if="!cloudIntegrations?.google?.configured"
                                type="button"
                                icon="pi pi-link"
                                label="Conectar Google Drive"
                                :disabled="readOnly"
                                @click="connectProvider('google')"
                            />
                            <Button
                                v-else
                                type="button"
                                icon="pi pi-check-circle"
                                label="Testar conexão"
                                outlined
                                :disabled="readOnly"
                                @click="testProvider('google')"
                            />
                            <Button
                                v-if="cloudIntegrations?.google?.configured"
                                type="button"
                                icon="pi pi-times"
                                label="Desconectar"
                                severity="danger"
                                outlined
                                :disabled="readOnly"
                                @click="disconnectProvider('google')"
                            />
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200/80 p-4 dark:border-slate-800">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h3 class="text-lg font-semibold">Dropbox</h3>
                            <Tag :severity="cloudIntegrations?.dropbox?.configured ? 'success' : 'secondary'" :value="cloudStatusText('dropbox')" />
                        </div>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            {{ cloudIntegrations?.dropbox?.account_email || 'Conecte sua conta para enviar arquivos diretamente para o Dropbox.' }}
                        </p>
                        <p class="mt-2 text-xs text-slate-400 dark:text-slate-500">
                            Callback autorizado no Dropbox app: {{ route('cloud.callback', { provider: 'dropbox' }) }}.
                        </p>
                        <div class="mt-3 space-y-2">
                            <label>Pasta base</label>
                            <div class="flex gap-2">
                                <InputText v-model="cloudIntegrationsLocal.dropbox.base_folder" :disabled="readOnly" placeholder="ERP_Arquivos" />
                                <Button type="button" icon="pi pi-save" outlined :disabled="readOnly" @click="saveProviderFolder('dropbox')" />
                            </div>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <Button
                                v-if="!cloudIntegrations?.dropbox?.configured"
                                type="button"
                                icon="pi pi-link"
                                label="Conectar Dropbox"
                                :disabled="readOnly"
                                @click="connectProvider('dropbox')"
                            />
                            <Button
                                v-else
                                type="button"
                                icon="pi pi-check-circle"
                                label="Testar conexão"
                                outlined
                                :disabled="readOnly"
                                @click="testProvider('dropbox')"
                            />
                            <Button
                                v-if="cloudIntegrations?.dropbox?.configured"
                                type="button"
                                icon="pi pi-times"
                                label="Desconectar"
                                severity="danger"
                                outlined
                                :disabled="readOnly"
                                @click="disconnectProvider('dropbox')"
                            />
                        </div>
                    </div>
                </div>
            </template>
        </Card>

        <Message v-if="readOnly" severity="info" size="small">
            Apenas administradores podem alterar identidade visual, cores dos módulos e integrações em nuvem.
        </Message>

        <Dialog
            :visible="showColorModal"
            modal
            :closable="true"
            :style="{ width: '860px', maxWidth: '95vw' }"
            @update:visible="(visible) => { if (!visible) closeColorModal(); }"
        >
            <template #header>
                <div class="flex items-center gap-2">
                    <Icon :icon="editingModule?.icon || 'ph:paint-brush-bold'" class="h-5 w-5" />
                    <span class="font-semibold">Cor do módulo {{ editingModule?.title || '' }}</span>
                </div>
            </template>

            <div class="space-y-4">
                <div class="grid max-h-[60vh] grid-cols-2 gap-2 overflow-y-auto pr-1 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                    <button
                        v-for="entry in colorPalette"
                        :key="entry.token"
                        type="button"
                        class="rounded-xl border p-2 text-left transition"
                        :class="selectedColorToken === entry.token ? 'border-slate-900 ring-2 ring-slate-400 dark:border-slate-100 dark:ring-slate-500' : 'border-slate-200 hover:border-slate-400 dark:border-slate-700 dark:hover:border-slate-500'"
                        @click="selectedColorToken = entry.token"
                    >
                        <span class="mb-2 block h-7 rounded-md" :style="{ backgroundColor: entry.hex }" />
                        <span class="block text-xs font-medium">{{ entry.token }}</span>
                    </button>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <Button type="button" label="Cancelar" outlined severity="secondary" @click="closeColorModal" />
                    <Button type="button" label="Salvar cor" icon="pi pi-save" :loading="moduleColorForm.processing" :disabled="readOnly" @click="saveModuleColor" />
                </div>
            </div>
        </Dialog>
    </div>
</template>
