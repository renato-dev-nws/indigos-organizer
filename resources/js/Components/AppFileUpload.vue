<script setup>
import axios from 'axios';
import { computed, ref } from 'vue';
import { Icon } from '@iconify/vue';
import BoConfirmButton from '@/Components/ui/BoConfirmButton.vue';

const props = defineProps({
    contentId: {
        type: String,
        required: true,
    },
    storeUrl: {
        type: String,
        required: true,
    },
    files: {
        type: Array,
        default: () => [],
    },
    maxFileSize: {
        type: Number,
        default: 50 * 1024 * 1024,
    },
    integrationConfigured: {
        type: Object,
        default: () => ({ google: { configured: false }, dropbox: { configured: false } }),
    },
});

const emit = defineEmits(['upload', 'remove']);
const selectedUploadProvider = ref('local');
const showUploader = ref(false);
const showAttachModal = ref(false);
const selectedAttachProvider = ref('google');
const browserNodes = ref([]);
const browserLoading = ref(false);
const expandedKeys = ref({});
const selectedTreeKey = ref({});
const selectedTreeNode = ref(null);
const uploadProgress = ref({ active: false, provider: 'local', value: 0, label: '' });
const browserError = ref('');

const canUploadGoogle = computed(() => !!props.integrationConfigured?.google?.configured);
const canUploadDropbox = computed(() => !!props.integrationConfigured?.dropbox?.configured);
const uploadHeader = computed(() => selectedUploadProvider.value === 'local'
    ? 'Upload local'
    : selectedUploadProvider.value === 'google'
        ? 'Upload para Google Drive'
        : 'Upload para Dropbox');

const uploadHeaderIcon = computed(() => {
    if (selectedUploadProvider.value === 'google') {
        return 'mdi:google-drive';
    }

    if (selectedUploadProvider.value === 'dropbox') {
        return 'mdi:dropbox';
    }

    return 'mdi:paperclip';
});

const googleActions = computed(() => ([
    {
        label: 'Upload para Google Drive',
        iconifyIcon: 'mdi:upload',
        command: () => openUploadPanel('google'),
    },
    {
        label: 'Anexar do Google Drive',
        iconifyIcon: 'mdi:link-variant',
        command: () => openAttachModal('google'),
    },
]));

const dropboxActions = computed(() => ([
    {
        label: 'Upload para Dropbox',
        iconifyIcon: 'mdi:upload',
        command: () => openUploadPanel('dropbox'),
    },
    {
        label: 'Anexar do Dropbox',
        iconifyIcon: 'mdi:link-variant',
        command: () => openAttachModal('dropbox'),
    },
]));

const setUploadProvider = (provider) => {
    selectedUploadProvider.value = provider;
};

const openUploadPanel = (provider) => {
    setUploadProvider(provider);
    showUploader.value = true;
};

const uploadFiles = async ({ files }) => {
    const uploads = files || [];
    if (!uploads.length) {
        return;
    }

    for (const file of uploads) {
        const formData = new FormData();
        formData.append('storage_source', selectedUploadProvider.value);
        formData.append('file', file);

        uploadProgress.value = {
            active: true,
            provider: selectedUploadProvider.value,
            value: 0,
            label: file.name,
        };

        try {
            await axios.post(props.storeUrl, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                onUploadProgress: (event) => {
                    if (!event.total) {
                        return;
                    }

                    uploadProgress.value.value = Math.round((event.loaded * 100) / event.total);
                },
            });

            emit('upload');
        } finally {
            uploadProgress.value = { active: false, provider: selectedUploadProvider.value, value: 0, label: '' };
        }
    }
};

const loadBrowserNodes = async (provider, path = '', options = {}) => {
    const { showGlobalLoading = false, clearError = true } = options;

    if (showGlobalLoading) {
        browserLoading.value = true;
    }

    if (clearError) {
        browserError.value = '';
    }

    try {
        const { data } = await axios.get(route('cloud.browser', { provider }), {
            params: { path },
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        return data.nodes || [];
    } catch (error) {
        browserError.value = error?.response?.data?.message || 'Nao foi possivel carregar os itens desta pasta.';
        throw error;
    } finally {
        if (showGlobalLoading) {
            browserLoading.value = false;
        }
    }
};

const patchNode = (nodes, key, transform) => nodes.map((node) => {
    if (node.key === key) {
        return transform(node);
    }

    if (Array.isArray(node.children) && node.children.length > 0) {
        return {
            ...node,
            children: patchNode(node.children, key, transform),
        };
    }

    return node;
});

const replaceNodeChildren = (nodes, key, children) => patchNode(nodes, key, (node) => ({
    ...node,
    children,
    leaf: children.length === 0,
    loading: false,
}));

const setNodeLoading = (key, loading) => {
    browserNodes.value = patchNode(browserNodes.value, key, (node) => ({
        ...node,
        loading,
    }));
};

const openAttachModal = async (provider) => {
    selectedAttachProvider.value = provider;
    selectedTreeKey.value = {};
    selectedTreeNode.value = null;
    expandedKeys.value = {};
    try {
        browserNodes.value = await loadBrowserNodes(provider, '', { showGlobalLoading: true });
    } catch {
        browserNodes.value = [];
    }

    showAttachModal.value = true;
};

const resolveTreeNodePayload = (payload) => payload?.node || payload;

const onNodeExpand = async (payload) => {
    const node = resolveTreeNodePayload(payload);
    if (!node?.key) {
        return;
    }

    setNodeLoading(node.key, true);

    try {
        const children = await loadBrowserNodes(selectedAttachProvider.value, node.data?.path || '', { showGlobalLoading: false });
        browserNodes.value = replaceNodeChildren(browserNodes.value, node.key, children);
    } catch {
        setNodeLoading(node.key, false);
    }
};

const onNodeSelect = (payload) => {
    const node = resolveTreeNodePayload(payload);
    selectedTreeNode.value = node?.type === 'file' ? node : null;
};

const attachSelectedFile = async () => {
    if (!selectedTreeNode.value?.data?.path) {
        return;
    }

    await axios.post(route('contents.files.attach', props.contentId), {
        storage_source: selectedAttachProvider.value,
        path: selectedTreeNode.value.data.path,
        original_name: selectedTreeNode.value.data.original_name,
        mime_type: selectedTreeNode.value.data.mime_type,
        size: selectedTreeNode.value.data.size,
    }, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
    });

    showAttachModal.value = false;
    emit('upload');
};

const formatSize = (size) => {
    if (!size && size !== 0) return '-';
    if (size < 1024) return `${size} B`;
    if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`;
    return `${(size / (1024 * 1024)).toFixed(2)} MB`;
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-wrap items-center gap-2">
            <Button type="button" icon="pi pi-paperclip" label="Upload local" :severity="selectedUploadProvider === 'local' && showUploader ? null : 'secondary'" @click="openUploadPanel('local')" />

            <SplitButton :model="googleActions" :disabled="!canUploadGoogle" severity="secondary" @click="openUploadPanel('google')">
                <span class="flex items-center gap-2 font-semibold">
                    <Icon icon="mdi:google-drive" class="h-4 w-4" />
                    <span>Google Drive</span>
                </span>
                <template #item="{ item, props: itemProps }">
                    <a v-ripple class="flex items-center gap-2" v-bind="itemProps.action">
                        <Icon :icon="item.iconifyIcon" class="h-4 w-4" />
                        <span>{{ item.label }}</span>
                    </a>
                </template>
            </SplitButton>

            <SplitButton :model="dropboxActions" :disabled="!canUploadDropbox" severity="secondary" @click="openUploadPanel('dropbox')">
                <span class="flex items-center gap-2 font-semibold">
                    <Icon icon="mdi:dropbox" class="h-4 w-4" />
                    <span>Dropbox</span>
                </span>
                <template #item="{ item, props: itemProps }">
                    <a v-ripple class="flex items-center gap-2" v-bind="itemProps.action">
                        <Icon :icon="item.iconifyIcon" class="h-4 w-4" />
                        <span>{{ item.label }}</span>
                    </a>
                </template>
            </SplitButton>
        </div>

        <div v-if="showUploader" class="rounded-xl border border-slate-200/80 p-4 dark:border-slate-800">
            <div class="mb-3 flex items-center justify-between gap-3">
                <div>
                    <h3 class="flex items-center gap-2 font-semibold text-slate-800 dark:text-slate-100">
                        <Icon :icon="uploadHeaderIcon" class="h-4 w-4" />
                        <span>{{ uploadHeader }}</span>
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Selecione os arquivos e clique em enviar para iniciar o upload.</p>
                </div>
                <div class="flex items-center gap-2">
                    <Tag :value="selectedUploadProvider === 'local' ? 'Local' : selectedUploadProvider === 'google' ? 'Drive' : 'Dropbox'" severity="secondary" />
                    <Button type="button" icon="pi pi-times" text rounded severity="secondary" aria-label="Fechar uploader" @click="showUploader = false" />
                </div>
            </div>

        <FileUpload
            mode="advanced"
            custom-upload
            :multiple="true"
            :max-file-size="maxFileSize"
            choose-label="Selecionar arquivos"
            upload-label="Enviar"
            cancel-label="Limpar"
            @uploader="uploadFiles"
        >
            <template #empty>
                <p class="m-0 text-sm text-slate-500 dark:text-slate-400">Arraste arquivos para esta area ou clique em selecionar.</p>
            </template>
        </FileUpload>

            <div v-if="uploadProgress.active" class="mt-3 space-y-2">
                <div class="flex items-center justify-between text-sm text-slate-600 dark:text-slate-300">
                    <span>Enviando {{ uploadProgress.label }}</span>
                    <span>{{ uploadProgress.value }}%</span>
                </div>
                <ProgressBar :value="uploadProgress.value" />
            </div>
        </div>

        <Dialog
            v-model:visible="showAttachModal"
            modal
            :style="{ width: '900px', maxWidth: '95vw' }"
            :header="selectedAttachProvider === 'google' ? 'Anexar do Google Drive' : 'Anexar do Dropbox'"
        >
            <div class="space-y-4">
                <p class="text-sm text-slate-500 dark:text-slate-400">Selecione um arquivo dentro da pasta base configurada para anexar ao conteúdo.</p>
                <Message v-if="browserError" severity="error" :closable="false" size="small">
                    {{ browserError }}
                </Message>
                <Tree
                    v-model:expandedKeys="expandedKeys"
                    v-model:selectionKeys="selectedTreeKey"
                    :value="browserNodes"
                    selectionMode="single"
                    :filter="true"
                    filterMode="lenient"
                    :loading="browserLoading"
                    loadingMode="icon"
                    class="max-h-[60vh] w-full overflow-auto rounded-xl border border-slate-200/80 p-3 dark:border-slate-800"
                    @node-expand="onNodeExpand"
                    @node-select="onNodeSelect"
                >
                    <template #default="slotProps">
                        <span>{{ slotProps.node.label }}</span>
                    </template>
                </Tree>
                <Message v-if="!browserLoading && !browserError && browserNodes.length === 0" severity="secondary" :closable="false" size="small">
                    Pasta vazia ou sem itens visiveis para a integracao.
                </Message>

                <div class="flex items-center justify-end gap-2">
                    <Button type="button" label="Cancelar" outlined severity="secondary" @click="showAttachModal = false" />
                    <Button type="button" label="Anexar" icon="pi pi-link" :disabled="!selectedTreeNode" @click="attachSelectedFile" />
                </div>
            </div>
        </Dialog>

        <DataTable :value="files" data-key="id" striped-rows size="small">
            <Column field="original_name" header="Arquivo">
                <template #body="{ data }">
                    <a v-if="data.url" :href="data.url" target="_blank" rel="noopener" class="text-indigo-600 underline dark:text-indigo-400">
                        {{ data.original_name }}
                    </a>
                    <span v-else>{{ data.original_name }}</span>
                </template>
            </Column>
            <Column field="storage_label" header="Local" />
            <Column field="mime_type" header="MIME" />
            <Column header="Tamanho">
                <template #body="{ data }">{{ formatSize(data.size) }}</template>
            </Column>
            <Column header="Ações" class="w-36">
                <template #body="{ data }">
                    <div class="flex items-center gap-2">
                        <BoConfirmButton
                            label="Remover"
                            icon="pi pi-trash"
                            severity="danger"
                            message="Deseja remover este arquivo?"
                            @confirm="emit('remove', data.id)"
                        />
                    </div>
                </template>
            </Column>
            <template #empty>
                <p class="py-2 text-sm text-slate-500 dark:text-slate-400">Nenhum arquivo anexado.</p>
            </template>
        </DataTable>
    </div>
</template>
