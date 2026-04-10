<script setup>
import BoConfirmButton from '@/Components/ui/BoConfirmButton.vue';

defineProps({
    files: {
        type: Array,
        default: () => [],
    },
    maxFileSize: {
        type: Number,
        default: 50 * 1024 * 1024,
    },
});

const emit = defineEmits(['upload', 'remove']);

const onUpload = ({ files }) => {
    emit('upload', files || []);
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
        <FileUpload
            mode="advanced"
            custom-upload
            auto
            :multiple="true"
            :max-file-size="maxFileSize"
            choose-label="Selecionar arquivos"
            upload-label="Enviar"
            cancel-label="Limpar"
            @uploader="onUpload"
        >
            <template #empty>
                <p class="m-0 text-sm text-slate-500 dark:text-slate-400">Arraste arquivos para esta area ou clique em selecionar.</p>
            </template>
        </FileUpload>

        <DataTable :value="files" data-key="id" striped-rows size="small">
            <Column field="original_name" header="Arquivo" />
            <Column field="mime_type" header="MIME" />
            <Column header="Tamanho">
                <template #body="{ data }">{{ formatSize(data.size) }}</template>
            </Column>
            <Column header="Acoes" class="w-36">
                <template #body="{ data }">
                    <BoConfirmButton
                        label="Remover"
                        icon="pi pi-trash"
                        severity="danger"
                        message="Deseja remover este arquivo?"
                        @confirm="emit('remove', data.id)"
                    />
                </template>
            </Column>
            <template #empty>
                <p class="py-2 text-sm text-slate-500 dark:text-slate-400">Nenhum arquivo anexado.</p>
            </template>
        </DataTable>
    </div>
</template>
