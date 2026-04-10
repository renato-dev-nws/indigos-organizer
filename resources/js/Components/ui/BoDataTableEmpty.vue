<template>
    <div class="flex flex-col items-center justify-center gap-3 py-10 text-center">
        <i class="pi pi-inbox text-4xl text-slate-400"></i>
        <div>
            <p class="text-lg font-semibold">Nenhum registro encontrado</p>
            <p class="text-sm text-slate-500 dark:text-slate-400">Ajuste os filtros ou crie um novo registro.</p>
        </div>
        <slot />
    </div>
</template>
