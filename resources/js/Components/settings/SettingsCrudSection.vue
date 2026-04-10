<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import draggable from 'vuedraggable';
import BoConfirmButton from '@/Components/ui/BoConfirmButton.vue';

const props = defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    items: { type: Array, default: () => [] },
    routeBase: { type: String, required: true },
    withColor: { type: Boolean, default: false },
    withOrder: { type: Boolean, default: false },
    disableDeleteWhen: { type: String, default: '' },
    disableDeleteMessage: { type: String, default: 'Este registro nao pode ser removido.' },
    reorderRoute: { type: String, default: '' },
});

const toast = useToast();
const dialogVisible = ref(false);
const editing = ref(null);
const orderDraft = ref([]);
const search = ref('');
const first = ref(0);
const rows = ref(8);

const form = reactive({
    name: '',
    color: '#4f46e5',
    order: 1,
});

const errors = reactive({
    name: '',
    color: '',
    order: '',
});

const clearErrors = () => {
    errors.name = '';
    errors.color = '';
    errors.order = '';
};

watch(
    () => props.items,
    (value) => {
        orderDraft.value = [...value];
    },
    { immediate: true },
);

watch(search, () => {
    first.value = 0;
});

const openCreate = () => {
    editing.value = null;
    clearErrors();
    form.name = '';
    form.color = '#4f46e5';
    form.order = (props.items.at(-1)?.order || 0) + 1;
    dialogVisible.value = true;
};

const openEdit = (item) => {
    editing.value = item;
    clearErrors();
    form.name = item.name;
    form.color = item.color || '#4f46e5';
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

    if (props.withOrder && (!Number.isInteger(form.order) || form.order < 1)) {
        errors.order = 'Ordem deve ser um inteiro maior ou igual a 1.';
    }

    return !errors.name && !errors.color && !errors.order;
};

const save = () => {
    if (!validate()) {
        return;
    }

    const payload = {
        name: form.name.trim(),
        ...(props.withColor ? { color: form.color } : {}),
        ...(props.withOrder ? { order: form.order } : {}),
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
    const term = search.value.trim().toLowerCase();
    if (!term) {
        return props.items;
    }

    return props.items.filter((item) => {
        const haystack = [item.name, item.color, String(item.order ?? '')]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();

        return haystack.includes(term);
    });
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
            <p v-if="description" class="mb-4 text-sm text-slate-500 dark:text-slate-400">{{ description }}</p>

            <div class="mb-4">
                <IconField class="w-full md:w-80">
                    <InputIcon class="pi pi-search" />
                    <InputText v-model="search" class="w-full" placeholder="Buscar por nome/cor" />
                </IconField>
            </div>

            <DataTable :value="paginatedItems" data-key="id" striped-rows>
                <Column field="name" header="Nome" />
                <Column v-if="withColor" field="color" header="Cor" class="w-40">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <span class="inline-block h-4 w-4 rounded" :style="{ backgroundColor: data.color }" />
                            <span>{{ data.color }}</span>
                        </div>
                    </template>
                </Column>
                <Column v-if="withOrder" field="order" header="Ordem" class="w-24" />
                <Column header="Ações" class="w-64">
                    <template #body="{ data }">
                        <div class="flex flex-wrap gap-2">
                            <Button icon="pi pi-pencil" label="Editar" size="small" outlined severity="secondary" @click="openEdit(data)" />
                            <BoConfirmButton
                                label="Excluir"
                                icon="pi pi-trash"
                                severity="danger"
                                :disabled="!canDelete(data)"
                                message="Deseja remover este registro?"
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
                :rows-per-page-options="[5, 8, 12, 20]"
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
