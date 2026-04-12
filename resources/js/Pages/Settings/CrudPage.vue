<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import SettingsCrudSection from '@/Components/settings/SettingsCrudSection.vue';

defineOptions({ layout: AppLayout });

defineProps({
    title: String,
    description: String,
    items: Array,
    routeBase: String,
    withColor: Boolean,
    withOrder: Boolean,
    reorderOnly: Boolean,
    disableDeleteWhen: String,
    disableDeleteMessage: String,
    reorderRoute: String,
    tabs: Array,
    activeTab: String,
});

const switchTab = (tab) => {
    if (!tab?.value) {
        return;
    }

    if (props.title === 'Tipos') {
        router.get(tab.value === 'venues' ? route('settings.pages.types.venues') : route('settings.pages.types'));
        return;
    }

    if (props.title === 'Categorias') {
        router.get(tab.value === 'venues' ? route('settings.pages.categories.venues') : route('settings.pages.categories'));
        return;
    }

    if (props.title === 'Estilos') {
        router.get(route('settings.pages.styles'));
    }
};
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader :title="title" :subtitle="description" />

        <div v-if="tabs?.length" class="max-w-md">
            <SelectButton
                :model-value="activeTab"
                :options="tabs"
                option-label="label"
                option-value="value"
                @change="switchTab($event.value)"
            />
        </div>

        <SettingsCrudSection
            :title="title"
            :description="description"
            :items="items"
            :route-base="routeBase"
            :with-color="withColor"
            :with-order="withOrder"
            :reorder-only="reorderOnly"
            :disable-delete-when="disableDeleteWhen"
            :disable-delete-message="disableDeleteMessage"
            :reorder-route="reorderRoute"
        />
    </div>
</template>
