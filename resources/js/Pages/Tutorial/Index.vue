<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    cards: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <div class="p-4 md:p-6 space-y-6">
        <BoPageHeader
            title="Tutorial"
            icon="mdi:help-rhombus-outline"
            module-key="tutorial"
        />

        <!-- Intro -->
        <div class="rounded-2xl bg-white/80 dark:bg-slate-900/70 ring-1 ring-slate-200/70 dark:ring-slate-800 shadow-sm p-5">
            <p class="text-slate-600 dark:text-slate-300 leading-relaxed">
                Bem-vindo ao <strong>Tutorial do Indigos Organizer</strong>! Aqui você encontra uma descrição completa de cada módulo do sistema, suas funcionalidades e regras de negócio. Clique em um card abaixo para acessar o tutorial de cada módulo.
            </p>
        </div>

        <!-- Module cards grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <Link
                v-for="card in cards"
                :key="card.slug"
                :href="`/docs/${card.slug}`"
                class="group flex flex-col gap-3 rounded-2xl bg-white/80 dark:bg-slate-900/70 ring-1 ring-slate-200/70 dark:ring-slate-800 shadow-sm p-5 hover:ring-indigo-400 hover:shadow-md transition-all"
            >
                <div class="flex items-center gap-3">
                    <span class="flex items-center justify-center rounded-xl bg-indigo-50 dark:bg-indigo-900/40 p-2 text-indigo-600 dark:text-indigo-400 group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/60 transition-colors">
                        <iconify-icon :icon="card.icon" width="24" height="24" />
                    </span>
                    <h2 class="font-semibold text-slate-800 dark:text-slate-100 text-base leading-tight">
                        {{ card.label }}
                    </h2>
                </div>
                <p
                    class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed line-clamp-3"
                    v-html="card.summary?.replace(/<br>/g, ' ').replaceAll('-', '•')"
                />
                <div class="mt-auto text-xs text-indigo-500 dark:text-indigo-400 group-hover:underline font-medium">
                    Ver tutorial →
                </div>
            </Link>
        </div>
    </div>
</template>
