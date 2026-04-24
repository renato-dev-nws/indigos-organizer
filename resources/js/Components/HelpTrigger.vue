<script setup>
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    module: {
        type: String,
        required: true,
    },
});

const visible = ref(false);
const summary = ref(null);
const loading = ref(false);

const open = async () => {
    visible.value = true;
    if (!summary.value && !loading.value) {
        loading.value = true;
        try {
            const response = await fetch(route('docs.summary', props.module));
            if (response.ok) {
                summary.value = await response.json();
            }
        } catch {
            // silently ignore
        } finally {
            loading.value = false;
        }
    }
};
</script>

<template>
    <!-- Superscript help icon — positioned like "º" in "1º" -->
    <span class="relative inline-block align-super">
        <button
            type="button"
            class="flex items-center justify-center text-slate-400 hover:text-indigo-500 transition-colors focus:outline-none"
            style="width:16px;height:16px;"
            title="Ajuda"
            @click.stop="open"
        >
            <iconify-icon icon="mdi:help-circle-outline" width="14" height="14" />
        </button>
    </span>

    <!-- Help dialog -->
    <Dialog
        v-model:visible="visible"
        modal
        :header="summary?.title ?? 'Ajuda'"
        :style="{ width: '480px', maxWidth: '95vw' }"
        :pt="{ header: { class: 'flex items-center gap-2' } }"
    >
        <div v-if="loading" class="flex justify-center py-8">
            <ProgressSpinner style="width:32px;height:32px" />
        </div>

        <div v-else-if="summary" class="space-y-3">
            <div
                class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed"
                v-html="summary.summary?.replace(/<br>/g, '<br/>')"
            />
        </div>

        <div v-else class="text-sm text-slate-500 py-4 text-center">
            Nenhuma informação de ajuda disponível.
        </div>

        <template #footer>
            <div class="flex justify-between items-center w-full">
                <Link
                    v-if="summary?.link"
                    :href="summary.link"
                    class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 underline"
                    @click="visible = false"
                >
                    Ver tutorial completo →
                </Link>
                <span v-else />
                <Button label="Fechar" severity="secondary" size="small" @click="visible = false" />
            </div>
        </template>
    </Dialog>
</template>
