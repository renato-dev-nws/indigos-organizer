<script setup>
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import { Icon } from '@iconify/vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    logoUrl: { type: String, default: null },
    iconUrl: { type: String, default: null },
    moduleDefinitions: { type: Array, default: () => [] },
    moduleColors: { type: Object, default: () => ({}) },
    colorPalette: { type: Array, default: () => [] },
    cloudIntegrations: { type: Object, default: () => ({}) },
    whatsAppSettings: { type: Object, default: () => ({}) },
});
const page = usePage();
const readOnly = !page.props.auth?.user?.is_admin;
const WHATSAPP_STATUS_POLL_INTERVAL_MS = 30000;

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
const whatsAppForm = useForm({
    evolution_instance: props.whatsAppSettings?.instance || 'main',
    evolution_whatsapp_user_routes: props.whatsAppSettings?.user_routes || '',
    evolution_whatsapp_group_routes: props.whatsAppSettings?.group_routes || '',
});
const whatsAppConnection = ref({
    status: 'unknown',
    qrBase64: null,
    pairingCode: null,
    loadingStatus: false,
    loadingQr: false,
    loadingDisconnect: false,
    error: '',
    info: '',
});
const whatsAppStatusPollInterval = ref(null);
const whatsAppQrAutoRefreshTimeout = ref(null);

const resolvedWhatsAppInstance = computed(() => {
    const instance = (whatsAppForm.evolution_instance || '').trim();

    return instance !== '' ? instance : 'main';
});
const isWhatsAppConnected = computed(() => ['open', 'connected'].includes(String(whatsAppConnection.value.status || '').toLowerCase()));
const whatsAppStatusLabel = computed(() => {
    const status = String(whatsAppConnection.value.status || '').toLowerCase();

    if (status === 'open' || status === 'connected') {
        return 'Conectado';
    }

    if (status === 'connecting') {
        return 'Conectando';
    }

    if (status === 'close' || status === 'closed' || status === 'disconnected') {
        return 'Desconectado';
    }

    return 'Nao conectado';
});
const whatsAppStatusSeverity = computed(() => {
    const status = String(whatsAppConnection.value.status || '').toLowerCase();

    if (status === 'open' || status === 'connected') {
        return 'success';
    }

    if (status === 'connecting') {
        return 'warning';
    }

    return 'secondary';
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

const saveWhatsAppSettings = () => {
    whatsAppForm.post(route('settings.system.update'), {
        preserveScroll: true,
    });
};

const clearWhatsAppQrAutoRefresh = () => {
    if (whatsAppQrAutoRefreshTimeout.value) {
        window.clearTimeout(whatsAppQrAutoRefreshTimeout.value);
        whatsAppQrAutoRefreshTimeout.value = null;
    }
};

const stopWhatsAppStatusPolling = () => {
    if (whatsAppStatusPollInterval.value) {
        window.clearInterval(whatsAppStatusPollInterval.value);
        whatsAppStatusPollInterval.value = null;
    }

    clearWhatsAppQrAutoRefresh();
};

const startWhatsAppStatusPolling = () => {
    if (whatsAppStatusPollInterval.value || readOnly) {
        return;
    }

    whatsAppStatusPollInterval.value = window.setInterval(() => {
        loadWhatsAppStatus({ silent: true });
    }, WHATSAPP_STATUS_POLL_INTERVAL_MS);
};

const extractConnectionState = (payload) => {
    const candidates = [
        payload?.state,
        payload?.status,
        payload?.connectionStatus,
        payload?.connectionState,
        payload?.instance?.state,
        payload?.instance?.status,
        payload?.instance?.connectionStatus,
    ];

    const first = candidates.find((candidate) => typeof candidate === 'string' && candidate.trim() !== '');

    return first ? first.trim().toLowerCase() : 'unknown';
};

const scheduleWhatsAppQrAutoRefresh = () => {
    clearWhatsAppQrAutoRefresh();

    whatsAppQrAutoRefreshTimeout.value = window.setTimeout(() => {
        if (!isWhatsAppConnected.value && !whatsAppConnection.value.loadingQr) {
            generateWhatsAppQr({ autoRefresh: true });
        }
    }, 35000);
};

const loadWhatsAppStatus = async ({ silent = false } = {}) => {
    if (readOnly || (!silent && whatsAppConnection.value.loadingStatus)) {
        return;
    }

    if (!silent) {
        whatsAppConnection.value.loadingStatus = true;
    }

    try {
        const response = await axios.get(route('settings.system.whatsapp.status', {}, false), {
            params: { instance: resolvedWhatsAppInstance.value },
        });

        const payload = response?.data?.data || {};
        const nextStatus = extractConnectionState(payload);

        whatsAppConnection.value.status = nextStatus;
        if (isWhatsAppConnected.value) {
            whatsAppConnection.value.qrBase64 = null;
            whatsAppConnection.value.pairingCode = null;
            stopWhatsAppStatusPolling();
            clearWhatsAppQrAutoRefresh();
        } else if (whatsAppConnection.value.qrBase64 || nextStatus === 'connecting') {
            startWhatsAppStatusPolling();
        }
    } catch (error) {
        if (!silent) {
            whatsAppConnection.value.error = error?.response?.data?.message || 'Nao foi possivel consultar o status da instancia.';
        }
    } finally {
        if (!silent) {
            whatsAppConnection.value.loadingStatus = false;
        }
    }
};

const generateWhatsAppQr = async ({ autoRefresh = false } = {}) => {
    if (readOnly || whatsAppConnection.value.loadingQr) {
        return;
    }

    whatsAppConnection.value.loadingQr = true;
    if (!autoRefresh) {
        whatsAppConnection.value.error = '';
        whatsAppConnection.value.info = 'Gerando QR Code...';
    }

    try {
        const response = await axios.get(route('settings.system.whatsapp.qr', {}, false), {
            params: { instance: resolvedWhatsAppInstance.value },
        });

        const payload = response?.data?.data || {};
        whatsAppConnection.value.qrBase64 = payload.base64 || null;
        whatsAppConnection.value.pairingCode = payload.pairingCode || null;
        whatsAppConnection.value.status = extractConnectionState(payload);
        whatsAppConnection.value.error = '';
        whatsAppConnection.value.info = 'QR Code atualizado. Ele sera renovado automaticamente em cerca de 35 segundos.';

        if (!whatsAppConnection.value.qrBase64) {
            whatsAppConnection.value.info = 'A instancia ainda esta conectando e o QR pode demorar alguns segundos para aparecer. Tentaremos novamente automaticamente.';
        }

        scheduleWhatsAppQrAutoRefresh();
        startWhatsAppStatusPolling();
    } catch (error) {
        whatsAppConnection.value.error = error?.response?.data?.message || 'Nao foi possivel gerar o QR Code.';
    } finally {
        whatsAppConnection.value.loadingQr = false;
    }
};

const disconnectWhatsAppInstance = async () => {
    if (readOnly || whatsAppConnection.value.loadingDisconnect) {
        return;
    }

    whatsAppConnection.value.loadingDisconnect = true;
    whatsAppConnection.value.error = '';

    try {
        await axios.post(route('settings.system.whatsapp.disconnect', {}, false), {
            instance: resolvedWhatsAppInstance.value,
        });

        whatsAppConnection.value.qrBase64 = null;
        whatsAppConnection.value.pairingCode = null;
        whatsAppConnection.value.info = 'Instancia desconectada. Gere um novo QR Code para reconectar.';
        clearWhatsAppQrAutoRefresh();

        await loadWhatsAppStatus({ silent: true });
    } catch (error) {
        whatsAppConnection.value.error = error?.response?.data?.message || 'Nao foi possivel desconectar a instancia.';
    } finally {
        whatsAppConnection.value.loadingDisconnect = false;
    }
};

onMounted(() => {
    if (readOnly) {
        return;
    }

    loadWhatsAppStatus();
});

onBeforeUnmount(() => {
    stopWhatsAppStatusPolling();
});
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

        <Card>
            <template #title>WhatsApp (Evolution API)</template>
            <template #content>
                <div class="space-y-4">
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Configure os destinos de WhatsApp no nivel do sistema. O envio so acontece para usuarios com notificacoes de WhatsApp habilitadas.
                    </p>

                    <div class="space-y-2">
                        <label>Nome da instância</label>
                        <InputText v-model="whatsAppForm.evolution_instance" :disabled="readOnly" placeholder="main" />
                        <small class="text-slate-500 dark:text-slate-400">Usado no endpoint da Evolution API (sendText/[instancia]).</small>
                        <Message v-if="whatsAppForm.errors.evolution_instance" severity="error" size="small" variant="simple">{{ whatsAppForm.errors.evolution_instance }}</Message>
                    </div>

                    <div class="space-y-2">
                        <label>Rotas de usuários (1 por linha)</label>
                        <Textarea
                            v-model="whatsAppForm.evolution_whatsapp_user_routes"
                            :disabled="readOnly"
                            rows="6"
                            autoResize
                            placeholder="user-uuid-1=5511999999999@s.whatsapp.net"
                            class="w-full"
                        />
                        <small class="text-slate-500 dark:text-slate-400">Formato: user_id=jid. Ex.: 8f1...=5511999999999@s.whatsapp.net</small>
                        <Message v-if="whatsAppForm.errors.evolution_whatsapp_user_routes" severity="error" size="small" variant="simple">{{ whatsAppForm.errors.evolution_whatsapp_user_routes }}</Message>
                    </div>

                    <div class="space-y-2">
                        <label>Rotas de grupos (1 por linha)</label>
                        <Textarea
                            v-model="whatsAppForm.evolution_whatsapp_group_routes"
                            :disabled="readOnly"
                            rows="5"
                            autoResize
                            placeholder="operacoes=1203630XXXXXX@g.us"
                            class="w-full"
                        />
                        <small class="text-slate-500 dark:text-slate-400">Formato: chave=jid_grupo. Ex.: operacoes=1203...@g.us</small>
                        <Message v-if="whatsAppForm.errors.evolution_whatsapp_group_routes" severity="error" size="small" variant="simple">{{ whatsAppForm.errors.evolution_whatsapp_group_routes }}</Message>
                    </div>

                    <div class="flex justify-end">
                        <Button
                            type="button"
                            icon="pi pi-save"
                            label="Salvar configuracao WhatsApp"
                            :loading="whatsAppForm.processing"
                            :disabled="readOnly"
                            @click="saveWhatsAppSettings"
                        />
                    </div>

                    <div class="rounded-xl border border-slate-200/80 p-4 dark:border-slate-800">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 class="text-base font-semibold text-slate-800 dark:text-slate-100">Conexao da instancia</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    Instancia ativa: <span class="font-semibold">{{ resolvedWhatsAppInstance }}</span>
                                </p>
                            </div>
                            <Tag :severity="whatsAppStatusSeverity" :value="whatsAppStatusLabel" />
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <Button
                                type="button"
                                icon="pi pi-refresh"
                                label="Atualizar status"
                                outlined
                                :loading="whatsAppConnection.loadingStatus"
                                :disabled="readOnly"
                                @click="loadWhatsAppStatus()"
                            />
                            <Button
                                type="button"
                                icon="pi pi-qrcode"
                                label="Gerar QR Code"
                                :loading="whatsAppConnection.loadingQr"
                                :disabled="readOnly"
                                @click="generateWhatsAppQr()"
                            />
                            <Button
                                v-if="isWhatsAppConnected"
                                type="button"
                                icon="pi pi-power-off"
                                label="Desconectar"
                                severity="danger"
                                outlined
                                :loading="whatsAppConnection.loadingDisconnect"
                                :disabled="readOnly"
                                @click="disconnectWhatsAppInstance"
                            />
                        </div>

                        <Message v-if="whatsAppConnection.error" class="mt-3" severity="error" size="small" variant="simple">
                            {{ whatsAppConnection.error }}
                        </Message>
                        <Message v-else-if="whatsAppConnection.info" class="mt-3" severity="info" size="small" variant="simple">
                            {{ whatsAppConnection.info }}
                        </Message>

                        <div v-if="whatsAppConnection.qrBase64 && !isWhatsAppConnected" class="mt-4 rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                            <div class="flex flex-col items-center gap-3">
                                <img :src="whatsAppConnection.qrBase64" alt="QR Code da instancia WhatsApp" class="h-56 w-56 rounded-lg border border-slate-200 bg-white p-2 dark:border-slate-700" />
                                <p v-if="whatsAppConnection.pairingCode" class="text-sm text-slate-500 dark:text-slate-400">
                                    Codigo de pareamento: <span class="font-semibold">{{ whatsAppConnection.pairingCode }}</span>
                                </p>
                                <p class="text-xs text-slate-400 dark:text-slate-500">
                                    O QR Code expira rapidamente. Esta tela renova automaticamente o codigo a cada ~35 segundos.
                                </p>
                            </div>
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
