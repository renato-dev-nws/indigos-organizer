<script setup>
import axios from 'axios';
import { computed, ref } from 'vue';
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
const showAttachModal = ref(false);
const selectedAttachProvider = ref('google');
const browserNodes = ref([]);
const browserLoading = ref(false);
const expandedKeys = ref({});
const selectedTreeKey = ref(null);
const selectedTreeNode = ref(null);
const uploadProgress = ref({ active: false, provider: 'local', value: 0, label: '' });

const canUploadGoogle = computed(() => !!props.integrationConfigured?.google?.configured);
const canUploadDropbox = computed(() => !!props.integrationConfigured?.dropbox?.configured);
const uploadHeader = computed(() => selectedUploadProvider.value === 'local'
    ? 'Upload local'
    : selectedUploadProvider.value === 'google'
        ? 'Upload para Google Drive'
        : 'Upload para Dropbox');

const setUploadProvider = (provider) => {
    selectedUploadProvider.value = provider;
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

const loadBrowserNodes = async (provider, path = '') => {
    browserLoading.value = true;

    try {
        const { data } = await axios.get(route('cloud.browser', { provider }), {
            params: { path },
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        return data.nodes || [];
    } finally {
        browserLoading.value = false;
    }
};

const openAttachModal = async (provider) => {
    selectedAttachProvider.value = provider;
    selectedTreeKey.value = null;
    selectedTreeNode.value = null;
    expandedKeys.value = {};
    browserNodes.value = await loadBrowserNodes(provider);
    showAttachModal.value = true;
};

const onNodeExpand = async (event) => {
    const children = await loadBrowserNodes(selectedAttachProvider.value, event.node.data?.path || '');
    event.node.children = children;
    browserNodes.value = [...browserNodes.value];
};

const onNodeSelect = (event) => {
    selectedTreeNode.value = event.node?.type === 'file' ? event.node : null;
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
            <Button type="button" icon="pi pi-paperclip" label="Enviar local" :severity="selectedUploadProvider === 'local' ? null : 'secondary'" @click="setUploadProvider('local')" />
            <Button
                type="button"
                icon="pi pi-cloud-upload"
                label="Enviar Drive"
                :disabled="!canUploadGoogle"
                :severity="selectedUploadProvider === 'google' ? null : 'secondary'"
                @click="setUploadProvider('google')"
            />
            <Button
                type="button"
                icon="pi pi-cloud-upload"
                label="Enviar Dropbox"
                :disabled="!canUploadDropbox"
                :severity="selectedUploadProvider === 'dropbox' ? null : 'secondary'"
                @click="setUploadProvider('dropbox')"
            />
            <Button
                type="button"
                icon="pi pi-folder-open"
                label="Anexar do Drive"
                :disabled="!canUploadGoogle"
                outlined
                @click="openAttachModal('google')"
            />
            <Button
                type="button"
                icon="pi pi-folder-open"
                label="Anexar do Dropbox"
                :disabled="!canUploadDropbox"
                outlined
                @click="openAttachModal('dropbox')"
            />
        </div>

        <div class="rounded-xl border border-slate-200/80 p-4 dark:border-slate-800">
            <div class="mb-3 flex items-center justify-between gap-3">
                <div>
                    <h3 class="font-semibold text-slate-800 dark:text-slate-100">{{ uploadHeader }}</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Selecione os arquivos e clique em enviar para iniciar o upload.</p>
                </div>
                <Tag :value="selectedUploadProvider === 'local' ? 'Local' : selectedUploadProvider === 'google' ? 'Drive' : 'Dropbox'" severity="secondary" />
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
                <Tree
                    v-model:expandedKeys="expandedKeys"
                    v-model:selectionKeys="selectedTreeKey"
                    :value="browserNodes"
                    selectionMode="single"
                    :filter="true"
                    filterMode="lenient"
                    :loading="browserLoading"
                    loadingMode="icon"
                    class="w-full rounded-xl border border-slate-200/80 p-3 dark:border-slate-800"
                    @nodeExpand="onNodeExpand"
                    @nodeSelect="onNodeSelect"
                >
                    <template #default="slotProps">
                        <div class="flex items-center gap-2">
                            <i :class="slotProps.node.icon" class="text-slate-500" />
                            <span>{{ slotProps.node.label }}</span>
                        </div>
                    </template>
                </Tree>

                <div class="flex items-center justify-end gap-2">
                    <Button type="button" label="Cancelar" outlined severity="secondary" @click="showAttachModal = false" />
                    <Button type="button" label="Anexar" icon="pi pi-link" :disabled="!selectedTreeNode" @click="attachSelectedFile" />
                </div>
            </div>
        </Dialog>

        <DataTable :value="files" data-key="id" striped-rows size="small">
            <Column field="original_name" header="Arquivo" />
            <Column field="storage_label" header="Local" />
            <Column field="mime_type" header="MIME" />
            <Column header="Tamanho">
                <template #body="{ data }">{{ formatSize(data.size) }}</template>
            </Column>
            <Column header="Ações" class="w-36">
                <template #body="{ data }">
                    <div class="flex items-center gap-2">
                        <a v-if="data.url" :href="data.url" target="_blank" rel="noopener" class="text-indigo-600 underline dark:text-indigo-400">Abrir</a>
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
