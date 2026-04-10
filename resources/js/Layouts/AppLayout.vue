<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import AppThemeSwitcher from '@/Components/AppThemeSwitcher.vue';

const page = usePage();

const links = [
    { label: 'Dashboard', href: route('dashboard') },
    { label: 'Ideias', href: route('ideas.index') },
    { label: 'Conteudos', href: route('contents.index') },
    { label: 'Tarefas', href: route('tasks.index') },
    { label: 'Casas de Show', href: route('venues.index') },
    { label: 'Configuracoes', href: route('settings.index') },
];
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-900 dark:text-slate-100">
        <header class="border-b border-slate-200 px-4 py-3 dark:border-slate-700">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4">
                <nav class="flex flex-wrap gap-3 text-sm">
                    <Link
                        v-for="item in links"
                        :key="item.label"
                        :href="item.href"
                        class="rounded px-2 py-1 hover:bg-slate-200 dark:hover:bg-slate-700"
                    >
                        {{ item.label }}
                    </Link>
                </nav>
                <div class="flex items-center gap-3">
                    <span class="text-sm">{{ page.props.auth?.user?.name }}</span>
                    <AppThemeSwitcher />
                    <Link method="post" :href="route('logout')" as="button" class="rounded border px-2 py-1 text-xs">Sair</Link>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl p-4">
            <slot />
        </main>
    </div>
</template>
