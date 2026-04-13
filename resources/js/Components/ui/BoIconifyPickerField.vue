<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { Icon } from '@iconify/vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    invalid: {
        type: Boolean,
        default: false,
    },
    inputId: {
        type: String,
        default: 'iconify-icon-input',
    },
    placeholder: {
        type: String,
        default: 'mdi:account',
    },
});

const emit = defineEmits(['update:modelValue']);

const visible = ref(false);
const loading = ref(false);
const error = ref('');
const query = ref('');
const prefix = ref('mdi');
const icons = ref([]);
let debounceTimer = null;

const iconValue = computed({
    get: () => props.modelValue || '',
    set: (value) => emit('update:modelValue', value),
});

const prefixOptions = [
    { label: 'Todos', value: '' },
    { label: 'MDI', value: 'mdi' },
    { label: 'IC', value: 'ic' },
    { label: 'Phosphor', value: 'ph' },
    { label: 'Tabler', value: 'tabler' },
    { label: 'Carbon', value: 'carbon' },
    { label: 'Material Symbols', value: 'material-symbols' },
];

const extractSearchFromModel = () => {
    const value = (iconValue.value || '').trim();
    if (!value.includes(':')) {
        return value;
    }

    return value.split(':').slice(1).join(':');
};

const runSearch = async () => {
    loading.value = true;
    error.value = '';

    try {
        const q = query.value.trim() || 'home';
        const params = new URLSearchParams({
            query: q,
            limit: '32',
        });

        if (prefix.value) {
            params.set('prefix', prefix.value);
        }

        const response = await fetch(`https://api.iconify.design/search?${params.toString()}`);
        if (!response.ok) {
            throw new Error(`Iconify status ${response.status}`);
        }

        const data = await response.json();
        icons.value = Array.isArray(data?.icons) ? data.icons.slice(0, 30) : [];
    } catch (fetchError) {
        console.error('Falha ao consultar API do Iconify:', fetchError);
        icons.value = [];
        error.value = 'Nao foi possivel buscar icones no momento.';
    } finally {
        loading.value = false;
    }
};

const scheduleSearch = () => {
    if (!visible.value) {
        return;
    }

    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }

    debounceTimer = setTimeout(() => {
        runSearch();
    }, 250);
};

const openPicker = () => {
    query.value = extractSearchFromModel();
    visible.value = true;
};

const pickIcon = (iconName) => {
    iconValue.value = iconName;
    visible.value = false;
};

watch([query, prefix], () => {
    scheduleSearch();
});

watch(visible, (isVisible) => {
    if (isVisible) {
        scheduleSearch();
    }
});

onBeforeUnmount(() => {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }
});
</script>

<template>
    <div class="space-y-2">
        <InputGroup>
            <InputText
                :id="inputId"
                v-model="iconValue"
                :placeholder="placeholder"
                fluid
                :invalid="invalid"
            />
            <InputGroupAddon class="min-w-12 justify-center p-0">
                <button
                    type="button"
                    class="flex h-full w-full items-center justify-center px-3 py-2 hover:bg-slate-100 dark:hover:bg-slate-800"
                    @click="openPicker"
                >
                    <Icon v-if="iconValue.trim()" :icon="iconValue.trim()" class="h-5 w-5" />
                    <i v-else class="pi pi-search text-slate-400" />
                </button>
            </InputGroupAddon>
        </InputGroup>

        <Dialog
            v-model:visible="visible"
            header="Selecionar icone"
            modal
            :draggable="false"
            :style="{ width: '56rem', maxWidth: '95vw' }"
        >
            <div class="space-y-3">
                <div class="grid gap-2 md:grid-cols-[220px_1fr]">
                    <Select
                        v-model="prefix"
                        :options="prefixOptions"
                        option-label="label"
                        option-value="value"
                        placeholder="Colecao"
                    />
                    <InputText
                        v-model="query"
                        placeholder="Buscar icones por texto"
                    />
                </div>

                <div class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                    <p v-if="loading" class="text-sm text-slate-500">Buscando icones...</p>
                    <p v-else-if="error" class="text-sm text-amber-600">{{ error }}</p>
                    <p v-else-if="!icons.length" class="text-sm text-slate-500">Nenhum icone encontrado.</p>

                    <div v-else class="grid grid-cols-4 gap-2 sm:grid-cols-5 md:grid-cols-7 lg:grid-cols-10">
                        <button
                            v-for="iconName in icons"
                            :key="iconName"
                            type="button"
                            class="rounded-lg border border-slate-200 p-2 text-center transition hover:border-indigo-400 hover:bg-indigo-50 dark:border-slate-700 dark:hover:bg-slate-800"
                            :title="iconName"
                            :aria-label="`Selecionar ícone ${iconName}`"
                            @click="pickIcon(iconName)"
                        >
                            <Icon :icon="iconName" class="mx-auto h-6 w-6" />
                        </button>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Fechar" text @click="visible = false" />
            </template>
        </Dialog>
    </div>
</template>
