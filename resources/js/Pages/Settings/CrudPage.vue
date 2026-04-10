<script setup>
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
    disableDeleteWhen: String,
    disableDeleteMessage: String,
    reorderRoute: String,
});
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader :title="title" :subtitle="description" />

        <SettingsCrudSection
            :title="title"
            :description="description"
            :items="items"
            :route-base="routeBase"
            :with-color="withColor"
            :with-order="withOrder"
            :disable-delete-when="disableDeleteWhen"
            :disable-delete-message="disableDeleteMessage"
            :reorder-route="reorderRoute"
        />
    </div>
</template>
