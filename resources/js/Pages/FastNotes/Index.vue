<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppSpeechTextareaAssist from '@/Components/AppSpeechTextareaAssist.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoConfirmButton from '@/Components/ui/BoConfirmButton.vue';
import BoDateText from '@/Components/ui/BoDateText.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    activeNotes: {
        type: Array,
        default: () => [],
    },
    archivedNotes: {
        type: Array,
        default: () => [],
    },
    showArchived: {
        type: Boolean,
        default: false,
    },
    archivedLimit: {
        type: Number,
        default: 16,
    },
    hasMoreArchived: {
        type: Boolean,
        default: false,
    },
    openCreate: {
        type: Boolean,
        default: false,
    },
});

const confirm = useConfirm();
const showModal = ref(false);
const editingNote = ref(null);

const form = useForm({
    title: '',
    related_type: 'administrative',
    list_items: [],
    note: '',
    is_priority: false,
});

const relatedTypeOptions = [
    { label: 'Administrativo', value: 'administrative' },
    { label: 'Conteudos', value: 'contents' },
    { label: 'Tarefas', value: 'tasks' },
    { label: 'Planejamento', value: 'planning' },
    { label: 'Outros', value: 'others' },
];

const listRows = computed(() => form.list_items || []);

const openCreateModal = () => {
    editingNote.value = null;
    form.defaults({
        title: '',
        related_type: 'administrative',
        list_items: [],
        note: '',
        is_priority: false,
    });
    form.reset();
    showModal.value = true;
};

const openEditModal = (note) => {
    editingNote.value = note;
    form.defaults({
        title: note.title || '',
        related_type: note.related_type || 'administrative',
        list_items: Array.isArray(note.list_items) ? note.list_items.map((item) => ({ item: item.item || '' })) : [],
        note: note.note || '',
        is_priority: !!note.is_priority,
    });
    form.reset();
    showModal.value = true;
};

watch(
    () => props.openCreate,
    (value) => {
        if (value) {
            openCreateModal();
        }
    },
    { immediate: true },
);

const closeModal = () => {
    showModal.value = false;
};

const addListItem = () => {
    form.list_items.push({ item: '' });
};

const removeListItem = (index) => {
    form.list_items.splice(index, 1);
};

const submit = () => {
    const payload = {
        ...form.data(),
        list_items: (form.list_items || []).filter((row) => String(row?.item || '').trim().length > 0),
    };

    if (editingNote.value?.id) {
        form.transform(() => payload).put(route('fast-notes.update', editingNote.value.id), {
            preserveScroll: true,
            onSuccess: closeModal,
        });
        return;
    }

    form.transform(() => payload).post(route('fast-notes.store'), {
        preserveScroll: true,
        onSuccess: closeModal,
    });
};

const confirmArchive = (note) => {
    confirm.require({
        header: 'Arquivar nota',
        message: 'Deseja arquivar esta nota rapida?',
        icon: 'pi pi-question-circle',
        rejectLabel: 'Cancelar',
        acceptLabel: 'Arquivar',
        accept: () => {
            router.patch(route('fast-notes.archive', note.id), {}, { preserveScroll: true });
            if (editingNote.value?.id === note.id) {
                closeModal();
            }
        },
    });
};

const archiveFromModal = () => {
    if (!editingNote.value?.id) {
        return;
    }

    confirmArchive(editingNote.value);
};

const toggleShowArchived = () => {
    router.get(route('fast-notes.index'), {
        show_archived: props.showArchived ? 0 : 1,
        archived_limit: props.archivedLimit,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const loadMoreArchived = () => {
    router.get(route('fast-notes.index'), {
        show_archived: 1,
        archived_limit: props.archivedLimit + 16,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const relatedTypeLabel = (value) => {
    return relatedTypeOptions.find((item) => item.value === value)?.label || value;
};

const noteItemsCount = (note) => {
    return Array.isArray(note?.list_items) ? note.list_items.length : 0;
};

const noteSummary = (note) => {
    const text = String(note?.note || '').trim();

    if (!text) {
        return 'Sem descrição.';
    }

    if (text.length <= 40) {
        return text;
    }

    return `${text.slice(0, 40)}...`;
};
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Notas rapidas" subtitle="Anotacoes e listas de acao em um clique" icon="mdi:notebook-edit-outline">
            <template #actions>
                <Button class="!hidden md:!inline-flex" icon="pi pi-plus" label="Nova nota" @click="openCreateModal" />
                <Button class="!inline-flex md:!hidden" icon="pi pi-plus" rounded aria-label="Nova nota" @click="openCreateModal" />
            </template>
        </BoPageHeader>

        <div class="flex justify-end">
            <Button
                type="button"
                :label="showArchived ? 'Ocultar notas arquivadas' : 'Exibir notas arquivadas'"
                outlined
                severity="secondary"
                @click="toggleShowArchived"
            />
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
            <div class="grid gap-3 grid-cols-1 md:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="note in activeNotes"
                    :key="note.id"
                    class="cursor-pointer rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-950"
                    @click="openEditModal(note)"
                >
                    <div class="mb-3 flex items-start justify-between gap-2">
                        <p class="line-clamp-2 text-sm font-semibold leading-snug text-slate-900 dark:text-slate-100">{{ note.title }}</p>
                        <Tag v-if="note.is_priority" value="Prioritaria" severity="danger" />
                    </div>

                    <div class="mb-3 inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-1 text-[11px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-200">
                        <iconify-icon icon="mdi:format-list-bulleted" width="12" height="12" />
                        <span>{{ noteItemsCount(note) }}</span>
                    </div>

                    <p class="mb-4 text-[11px] leading-tight text-slate-500 dark:text-slate-400">{{ noteSummary(note) }}</p>

                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs text-slate-500">
                            <BoDateText :value="note.created_at" mode="datetime" />
                        </p>
                        <div class="flex items-center gap-1" @click.stop>
                            <Button
                                icon="pi pi-box"
                                size="small"
                                rounded
                                outlined
                                severity="secondary"
                                aria-label="Arquivar"
                                @click="confirmArchive(note)"
                            />
                            <Button
                                icon="pi pi-pencil"
                                size="small"
                                rounded
                                outlined
                                severity="secondary"
                                aria-label="Editar"
                                @click="openEditModal(note)"
                            />
                            <BoConfirmButton
                                icon="pi pi-trash"
                                size="small"
                                severity="danger"
                                :rounded="true"
                                message="Deseja remover esta nota rapida?"
                                @confirm="router.delete(route('fast-notes.destroy', note.id), { preserveScroll: true })"
                            />
                        </div>
                    </div>
                </article>

                <p v-if="!activeNotes.length" class="col-span-full text-sm text-slate-500">Nenhuma nota rapida ativa.</p>
            </div>
        </div>

        <div v-if="showArchived" class="space-y-4">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Notas arquivadas</h3>

            <div class="grid gap-3 grid-cols-1 md:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="note in archivedNotes"
                    :key="note.id"
                    class="cursor-pointer rounded-xl border border-slate-200 bg-slate-50 p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
                    @click="openEditModal(note)"
                >
                    <div class="mb-5 flex items-start justify-between gap-2">
                        <p class="line-clamp-2 text-sm font-semibold leading-snug text-slate-800 dark:text-slate-100">{{ note.title }}</p>
                        <Tag value="Arquivada" severity="secondary" />
                    </div>

                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs text-slate-500">
                            <BoDateText :value="note.archived_at || note.updated_at" mode="datetime" />
                        </p>
                        <div class="flex items-center gap-1" @click.stop>
                            <Button
                                icon="pi pi-pencil"
                                size="small"
                                rounded
                                outlined
                                severity="secondary"
                                aria-label="Editar"
                                @click="openEditModal(note)"
                            />
                            <BoConfirmButton
                                icon="pi pi-trash"
                                size="small"
                                severity="danger"
                                :rounded="true"
                                message="Deseja remover esta nota rapida?"
                                @confirm="router.delete(route('fast-notes.destroy', note.id), { preserveScroll: true })"
                            />
                        </div>
                    </div>
                </article>

                <p v-if="!archivedNotes.length" class="col-span-full text-sm text-slate-500">Nenhuma nota arquivada.</p>
            </div>

            <div class="flex justify-center" v-if="hasMoreArchived">
                <Button type="button" label="Ver mais antigas" outlined severity="secondary" @click="loadMoreArchived" />
            </div>
        </div>

        <Dialog
            :visible="showModal"
            modal
            :header="editingNote ? 'Editar nota rapida' : 'Nova nota rapida'"
            :style="{ width: '44rem', maxWidth: '96vw' }"
            @update:visible="showModal = $event"
        >
            <form class="space-y-4" @submit.prevent="submit">
                <div class="space-y-2">
                    <label for="fast-note-title">Título</label>
                    <InputText id="fast-note-title" v-model="form.title" placeholder="Opcional" fluid />
                </div>

                <div class="space-y-2">
                    <label>Referencia relacionada</label>
                    <Select
                        v-model="form.related_type"
                        :options="relatedTypeOptions"
                        option-label="label"
                        option-value="value"
                        fluid
                    />
                </div>

                <div class="space-y-2">
                    <label>Lista</label>
                    <div class="space-y-2">
                        <div v-for="(row, index) in listRows" :key="index" class="flex items-center gap-2">
                            <InputText v-model="row.item" class="w-full" placeholder="Item da lista" />
                            <Button type="button" icon="pi pi-trash" text severity="danger" @click="removeListItem(index)" />
                        </div>
                    </div>
                    <Button type="button" icon="pi pi-plus" label="Adicionar item" outlined severity="secondary" @click="addListItem" />
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <label>Nota</label>
                        <AppSpeechTextareaAssist v-model="form.note" />
                    </div>
                    <Textarea v-model="form.note" rows="6" fluid />
                </div>

                <div class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-700">
                    <ToggleSwitch v-model="form.is_priority" input-id="fast-note-priority" />
                    <label for="fast-note-priority" class="text-sm">Nota Prioritaria</label>
                </div>

                <div class="flex justify-end gap-2">
                    <Button type="button" label="Cancelar" outlined severity="secondary" @click="closeModal" />
                    <Button v-if="editingNote" type="button" label="Arquivar" outlined severity="secondary" @click="archiveFromModal" />
                    <Button type="submit" label="Salvar" icon="pi pi-save" :loading="form.processing" />
                </div>

                <Message v-if="form.hasErrors" severity="error" size="small" variant="simple">
                    {{ Object.values(form.errors)[0] }}
                </Message>
            </form>
        </Dialog>
    </div>
</template>
