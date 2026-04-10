<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import AppThemeSwitcher from '@/Components/AppThemeSwitcher.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';

defineOptions({ layout: AppLayout });
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
                    <Card>
                        <template #title>Tipos e categorias de ideia</template>
                        <template #content>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Estrutura pronta para CRUD inline com DataTable + Dialog, mantendo o backend atual.</p>
                        </template>
                    </Card>
                </TabPanel>

                <TabPanel value="2">
                    <Card>
                        <template #title>Plataformas, tipos e categorias de conteudo</template>
                        <template #content>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Estrutura pronta para CRUD inline por aba de conteudo, sem alterar contratos de API.</p>
                        </template>
                    </Card>
                </TabPanel>

                <TabPanel value="3">
                    <Card>
                        <template #title>Status de tarefas</template>
                        <template #content>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Preparado para listagem e reorder drag-and-drop quando os endpoints de settings estiverem expostos.</p>
                        </template>
                    </Card>
                </TabPanel>
            </TabPanels>
        </Tabs>
    </div>
</template>
