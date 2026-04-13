<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import draggable from 'vuedraggable';
import BoConfirmButton from '@/Components/ui/BoConfirmButton.vue';
import BoIconifyPickerField from '@/Components/ui/BoIconifyPickerField.vue';

const props = defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    items: { type: Array, default: () => [] },
    routeBase: { type: String, required: true },
    withColor: { type: Boolean, default: false },
    withIcon: { type: Boolean, default: false },
    withOrder: { type: Boolean, default: false },
    reorderOnly: { type: Boolean, default: false },
    disableDeleteWhen: { type: String, default: '' },
    disableDeleteMessage: { type: String, default: 'Este registro nao pode ser removido.' },
    reorderRoute: { type: String, default: '' },
    extraPayload: { type: Object, default: () => ({}) },
});

const toast = useToast();
const dialogVisible = ref(false);
const editing = ref(null);
const orderDraft = ref([]);
const first = ref(0);
const rows = ref(8);

const form = reactive({
    name: '',
    color: '#4f46e5',
    icon: '',
    order: 1,
});

const errors = reactive({
    name: '',
    color: '',
    icon: '',
    order: '',
});

const clearErrors = () => {
    errors.name = '';
    errors.color = '';
    errors.icon = '';
    errors.order = '';
};

watch(
    () => props.items,
    (value) => {
        orderDraft.value = [...value];
    },
    { immediate: true },
);

const openCreate = () => {
    editing.value = null;
    clearErrors();
    form.name = '';
    form.color = '#4f46e5';
    form.icon = '';
    form.order = (props.items.at(-1)?.order || 0) + 1;
    dialogVisible.value = true;
};

const openEdit = (item) => {
    editing.value = item;
    clearErrors();
    form.name = item.name;
    form.color = item.color || '#4f46e5';
    form.icon = item.icon || '';
    form.order = item.order || 1;
    dialogVisible.value = true;
};

const validate = () => {
    clearErrors();

    if (!form.name?.trim()) {
        errors.name = 'Nome e obrigatorio.';
    } else if (form.name.trim().length > 255) {
        errors.name = 'Nome deve ter no maximo 255 caracteres.';
    }

    if (props.withColor && !/^#[0-9a-fA-F]{6}$/.test(form.color || '')) {
        errors.color = 'Cor deve estar no formato #RRGGBB.';
    }

    if (props.withIcon && form.icon && form.icon.trim().length > 100) {
        errors.icon = 'Ícone deve ter no máximo 100 caracteres.';
    }

    if (props.withIcon && form.icon && !/^[a-z0-9-]+:[a-z0-9-]+$/i.test(form.icon.trim())) {
        errors.icon = 'Use formato Iconify no padrão prefixo:nome. Exemplo: mdi:account.';
    }

    if (props.withOrder && (!Number.isInteger(form.order) || form.order < 1)) {
        errors.order = 'Ordem deve ser um inteiro maior ou igual a 1.';
    }

    return !errors.name && !errors.color && !errors.icon && !errors.order;
};

const save = () => {
    if (!validate()) {
        return;
    }

    const payload = {
        name: form.name.trim(),
        ...(props.withColor ? { color: form.color } : {}),
        ...(props.withIcon ? { icon: (form.icon || '').trim() || null } : {}),
        ...(props.withOrder ? { order: form.order } : {}),
        ...props.extraPayload,
    };

    if (editing.value) {
        router.put(route(`${props.routeBase}.update`, editing.value.id), payload, {
            preserveScroll: true,
            onSuccess: () => {
                dialogVisible.value = false;
            },
        });
        return;
    }

    router.post(route(`${props.routeBase}.store`), payload, {
        preserveScroll: true,
        onSuccess: () => {
            dialogVisible.value = false;
        },
    });
};

const remove = (item) => {
    if (!canDelete(item)) {
        toast.add({ severity: 'warn', summary: 'Acao bloqueada', detail: props.disableDeleteMessage, life: 3500 });
        return;
    }

    router.delete(route(`${props.routeBase}.destroy`, item.id), { preserveScroll: true });
};

const saveOrder = () => {
    if (!props.reorderRoute) return;

    router.patch(route(props.reorderRoute), {
        ordered_ids: orderDraft.value.map((item) => item.id),
    }, {
        preserveScroll: true,
    });
};

const hasReorder = computed(() => !!props.reorderRoute);

const filteredItems = computed(() => {
    return props.items;
});

const paginatedItems = computed(() => {
    return filteredItems.value.slice(first.value, first.value + rows.value);
});

const canDelete = (item) => {
    return !(props.disableDeleteWhen && Number(item[props.disableDeleteWhen] || 0) > 0);
};

const onPage = (event) => {
    first.value = event.first;
    rows.value = event.rows;
};
</script>

<template>
    <Card>
        <template #title>
            <div class="flex items-center justify-between gap-3">
                <span>{{ title }}</span>
                <Button icon="pi pi-plus" label="Novo" size="small" @click="openCreate" />
            </div>
        </template>
        <template #content>
            <p v-if="description" class="mb-4 text-sm text-muted-color">{{ description }}</p>

            <!-- Reorder-only mode: show only draggable list (e.g. task statuses) -->
            <template v-if="reorderOnly && hasReorder">
                <draggable :list="orderDraft" item-key="id" handle=".drag-handle" class="space-y-2">
                    <template #item="{ element, index }">
                        <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-white p-2.5 dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-center gap-3">
                                <i class="pi pi-bars drag-handle cursor-grab text-slate-400 dark:text-slate-600" />
                                <div v-if="withColor" class="h-3 w-3 rounded-full" :style="{ backgroundColor: element.color }" />
                                <span class="text-sm font-medium">{{ index + 1 }}. {{ element.name }}</span>
                            </div>
                            <div class="flex gap-1">
                                <Button icon="pi pi-pencil" size="small" text rounded severity="secondary" v-tooltip.top="'Editar'" @click="openEdit(element)" />
                                <BoConfirmButton
                                    icon="pi pi-trash"
                                    severity="danger"
                                    :disabled="!canDelete(element)"
                                    message="Deseja remover este registro?"
                                    :rounded="true"
                                    @confirm="remove(element)"
                                />
                            </div>
                        </div>
                    </template>
                </draggable>
                <Button class="mt-3" icon="pi pi-save" label="Salvar ordem" size="small" @click="saveOrder" />
            </template>

            <!-- Normal mode: DataTable + optional reorder section -->
            <template v-else>
                <DataTable :value="paginatedItems" data-key="id" striped-rows :sort-mode="'single'" removable-sort>
                    <Column field="name" header="Nome" sortable />
                    <Column v-if="withIcon" field="icon" header="Ícone" class="w-40">
                        <template #body="{ data }">
                            <div class="flex items-center justify-center">
                                <iconify-icon v-if="data.icon" :icon="data.icon" width="18" height="18" />
                                <span v-else class="text-xs text-slate-400">-</span>
                            </div>
                        </template>
                    </Column>
                    <Column v-if="withColor" field="color" header="Cor" class="w-40">
                        <template #body="{ data }">
                            <div class="flex items-center gap-2">
                                <span class="inline-block h-4 w-4 rounded" :style="{ backgroundColor: data.color }" />
                                <span>{{ data.color }}</span>
                            </div>
                        </template>
                    </Column>
                    <Column v-if="withOrder" field="order" header="Ordem" class="w-24" />
                    <Column header="Ações" class="bo-action-col w-20">
                        <template #body="{ data }">
                            <div class="flex gap-1">
                                <Button icon="pi pi-pencil" size="small" outlined rounded severity="secondary" v-tooltip.top="'Editar'" @click="openEdit(data)" />
                                <BoConfirmButton
                                    icon="pi pi-trash"
                                    severity="danger"
                                    :disabled="!canDelete(data)"
                                    message="Deseja remover este registro?"
                                    :rounded="true"
                                    @confirm="remove(data)"
                                />
                            </div>
                        </template>
                    </Column>
                </DataTable>

                <Paginator
                    class="mt-3"
                    :rows="rows"
                    :first="first"
                    :total-records="filteredItems.length"
                    @page="onPage"
                />

                <div v-if="hasReorder" class="mt-6 rounded-xl border border-dashed border-slate-300 p-4 dark:border-slate-700">
                    <p class="mb-2 text-sm font-semibold">Reordenar status (arraste e solte)</p>
                    <draggable :list="orderDraft" item-key="id" handle=".drag-handle" class="space-y-2">
                        <template #item="{ element, index }">
                            <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-white p-2 dark:border-slate-800 dark:bg-slate-900">
                                <div class="flex items-center gap-2">
                                    <i class="pi pi-bars drag-handle cursor-grab text-slate-500" />
                                    <span class="text-sm">{{ index + 1 }}. {{ element.name }}</span>
                                </div>
                                <Tag :value="element.color" :style="{ backgroundColor: element.color, color: '#fff' }" />
                            </div>
                        </template>
                    </draggable>
                    <Button class="mt-3" icon="pi pi-save" label="Salvar ordem" size="small" @click="saveOrder" />
                </div>
            </template>
        </template>
    </Card>

    <Dialog v-model:visible="dialogVisible" :header="editing ? 'Editar registro' : 'Novo registro'" modal :style="{ width: '32rem' }">
        <div class="space-y-4">
            <div class="space-y-2">
                <label for="settings-name">Nome</label>
                <InputText id="settings-name" v-model="form.name" fluid :invalid="!!errors.name" />
                <Message v-if="errors.name" severity="error" size="small" variant="simple">{{ errors.name }}</Message>
            </div>
            <div v-if="withColor" class="space-y-2">
                <label for="settings-color">Cor (hex)</label>
                <InputText id="settings-color" v-model="form.color" fluid placeholder="#4f46e5" :invalid="!!errors.color" />
                <Message v-if="errors.color" severity="error" size="small" variant="simple">{{ errors.color }}</Message>
            </div>
            <div v-if="withIcon" class="space-y-2">
                <label for="settings-icon">Ícone (Iconify)</label>
                <BoIconifyPickerField
                    input-id="settings-icon"
                    v-model="form.icon"
                    :invalid="!!errors.icon"
                    placeholder="mdi:account"
                />
                <small class="text-slate-500">Use formato prefixo:nome. Exemplo: mdi:account.</small>
                <Message v-if="errors.icon" severity="error" size="small" variant="simple">{{ errors.icon }}</Message>
            </div>
            <div v-if="withOrder" class="space-y-2">
                <label for="settings-order">Ordem</label>
                <InputNumber id="settings-order" v-model="form.order" :min="1" fluid :invalid="!!errors.order" />
                <Message v-if="errors.order" severity="error" size="small" variant="simple">{{ errors.order }}</Message>
            </div>
        </div>

        <template #footer>
            <Button label="Cancelar" text @click="dialogVisible = false" />
            <Button label="Salvar" icon="pi pi-check" @click="save" />
        </template>
    </Dialog>
</template>
