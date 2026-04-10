<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppThemeSwitcher from '@/Components/AppThemeSwitcher.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import SettingsCrudSection from '@/Components/settings/SettingsCrudSection.vue';

defineOptions({ layout: AppLayout });

defineProps({
    ideaTypes: Array,
    ideaCategories: Array,
    contentPlatforms: Array,
    contentTypes: Array,
    contentCategories: Array,
    taskStatuses: Array,
});
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Configuracoes" subtitle="Tema e cadastros auxiliares" />

        <Tabs value="0">
            <TabList>
                <Tab value="0">Tema</Tab>
                <Tab value="1">Tipos e categorias de ideia</Tab>
                <Tab value="2">Conteudos</Tab>
                <Tab value="3">Status de tarefas</Tab>
            </TabList>
            <TabPanels>
                <TabPanel value="0">
                    <Card>
                        <template #title>Tema da interface</template>
                        <template #content>
                            <p class="mb-3 text-sm text-slate-500 dark:text-slate-400">Selecione o modo de exibicao da aplicacao.</p>
                            <AppThemeSwitcher />
                        </template>
                    </Card>
                </TabPanel>

                <TabPanel value="1">
                    <div class="grid gap-4 lg:grid-cols-2">
                        <SettingsCrudSection
                            title="Tipos de ideia"
                            description="Gerencie tipos com nome e cor."
                            :items="ideaTypes"
                            route-base="settings.idea-types"
                            with-color
                            disable-delete-when="ideas_count"
                            disable-delete-message="Nao e permitido excluir tipo com ideias vinculadas."
                        />

                        <SettingsCrudSection
                            title="Categorias de ideia"
                            description="Gerencie categorias usadas nas ideias."
                            :items="ideaCategories"
                            route-base="settings.idea-categories"
                        />
                    </div>
                </TabPanel>

                <TabPanel value="2">
                    <div class="grid gap-4 lg:grid-cols-2">
                        <SettingsCrudSection
                            title="Plataformas de conteudo"
                            description="Ex.: TikTok, Instagram, YouTube."
                            :items="contentPlatforms"
                            route-base="settings.content-platforms"
                        />

                        <SettingsCrudSection
                            title="Tipos de conteudo"
                            description="Ex.: Reel, Shorts, Story."
                            :items="contentTypes"
                            route-base="settings.content-types"
                        />

                        <SettingsCrudSection
                            class="lg:col-span-2"
                            title="Categorias de conteudo"
                            description="Categorias com cores para classificacao."
                            :items="contentCategories"
                            route-base="settings.content-categories"
                            with-color
                        />
                    </div>
                </TabPanel>

                <TabPanel value="3">
                    <SettingsCrudSection
                        title="Status de tarefas"
                        description="CRUD inline completo com reorder drag-and-drop."
                        :items="taskStatuses"
                        route-base="settings.task-statuses"
                        with-color
                        with-order
                        disable-delete-when="tasks_count"
                        disable-delete-message="Nao e permitido remover status com tarefas vinculadas."
                        reorder-route="settings.task-statuses.reorder"
                    />
                </TabPanel>
            </TabPanels>
        </Tabs>
    </div>
</template>
