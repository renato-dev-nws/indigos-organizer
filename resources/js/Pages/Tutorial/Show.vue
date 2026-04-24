<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    module: {
        type: Object,
        required: true,
    },
    content: {
        type: String,
        default: '',
    },
    modules: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <div class="p-4 md:p-6 space-y-6">
        <BoPageHeader
            :title="module.label"
            :icon="module.icon"
            module-key="tutorial"
        >
            <template #actions>
                <Link
                    :href="route('docs.index')"
                    class="flex items-center gap-1 rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-1.5 text-sm text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                >
                    <iconify-icon icon="mdi:arrow-left" width="16" height="16" />
                    Tutorial
                </Link>
            </template>
        </BoPageHeader>

        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Doc content -->
            <div class="flex-1 min-w-0">
                <div
                    v-if="content"
                    class="prose prose-slate dark:prose-invert max-w-none rounded-2xl bg-white/80 dark:bg-slate-900/70 ring-1 ring-slate-200/70 dark:ring-slate-800 shadow-sm p-6"
                    v-html="content"
                />
                <div
                    v-else
                    class="rounded-2xl bg-white/80 dark:bg-slate-900/70 ring-1 ring-slate-200/70 dark:ring-slate-800 shadow-sm p-8 text-center text-slate-500 dark:text-slate-400"
                >
                    Documentação ainda não disponível para este módulo.
                </div>
            </div>

            <!-- Module sidebar nav -->
            <aside class="lg:w-56 shrink-0">
                <div class="rounded-2xl bg-white/80 dark:bg-slate-900/70 ring-1 ring-slate-200/70 dark:ring-slate-800 shadow-sm p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-3">
                        Módulos
                    </p>
                    <nav class="space-y-1">
                        <Link
                            v-for="mod in modules"
                            :key="mod.slug"
                            :href="`/docs/${mod.slug}`"
                            class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm transition-colors"
                            :class="mod.slug === module.slug
                                ? 'bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 font-medium'
                                : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'"
                        >
                            <iconify-icon :icon="mod.icon" width="16" height="16" class="shrink-0" />
                            {{ mod.label }}
                        </Link>
                    </nav>
                </div>
            </aside>
        </div>
    </div>
</template>

<style scoped>
/* Prose styles for rendered markdown */
:deep(.prose) {
    --tw-prose-headings: theme('colors.slate.800');
    --tw-prose-body: theme('colors.slate.700');
    --tw-prose-bold: theme('colors.slate.900');
    --tw-prose-links: theme('colors.indigo.600');
    --tw-prose-bullets: theme('colors.indigo.400');
    --tw-prose-hr: theme('colors.slate.200');
    --tw-prose-code: theme('colors.indigo.700');
    --tw-prose-code-bg: theme('colors.indigo.50');
}

:deep(.dark .prose),
:deep(.prose-invert) {
    --tw-prose-headings: theme('colors.slate.100');
    --tw-prose-body: theme('colors.slate.300');
    --tw-prose-bold: theme('colors.slate.100');
    --tw-prose-links: theme('colors.indigo.400');
    --tw-prose-bullets: theme('colors.indigo.500');
    --tw-prose-hr: theme('colors.slate.700');
    --tw-prose-code: theme('colors.indigo.300');
    --tw-prose-code-bg: theme('colors.indigo.900');
}

:deep(.prose h1),
:deep(.prose h2),
:deep(.prose h3) {
    font-weight: 700;
    margin-top: 1.25em;
    margin-bottom: 0.5em;
}

:deep(.prose h1) { font-size: 1.5rem; }
:deep(.prose h2) { font-size: 1.25rem; }
:deep(.prose h3) { font-size: 1.1rem; }

:deep(.prose ul) {
    list-style-type: disc;
    padding-left: 1.5rem;
    margin: 0.5em 0;
}

:deep(.prose ol) {
    list-style-type: decimal;
    padding-left: 1.5rem;
    margin: 0.5em 0;
}

:deep(.prose li) { margin: 0.25em 0; }

:deep(.prose hr) {
    border-top: 1px solid;
    margin: 1.5em 0;
    opacity: 0.3;
}

:deep(.prose strong) { font-weight: 600; }

:deep(.prose blockquote) {
    border-left: 4px solid theme('colors.indigo.400');
    padding-left: 1rem;
    margin-left: 0;
    color: theme('colors.slate.500');
    font-style: italic;
}

:deep(.prose code) {
    font-size: 0.875em;
    padding: 0.1em 0.4em;
    border-radius: 4px;
    background: theme('colors.indigo.50');
    color: theme('colors.indigo.700');
}
</style>
