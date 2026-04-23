<script setup>
import { ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import SettingsCrudSection from '@/Components/settings/SettingsCrudSection.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    title: String,
    description: String,
    items: Array,
    routeBase: String,
    withColor: Boolean,
    withIcon: Boolean,
    withOrder: Boolean,
    reorderOnly: Boolean,
    disableDeleteWhen: String,
    disableDeleteNames: Array,
    disableDeleteMessage: String,
    reorderRoute: String,
    tabs: Array,
    tabRoutes: Object,
    activeTab: String,
    extraPayload: Object,
});

const selectedTab = ref(props.activeTab);
const page = usePage();
const readOnly = !page.props.auth?.user?.is_admin;

const pageTitle = `Configurações de ${props.title || ''}`.trim();
const sectionTitle = `Configuração: ${props.description || props.title || ''}`.replace(/\.$/, '');

watch(() => props.activeTab, (value) => {
    selectedTab.value = value;
});

const switchTab = (tabValue) => {
    const value = typeof tabValue === 'string' ? tabValue : tabValue?.value;
    if (!value || value === props.activeTab) {
        return;
    }

    const targetRoute = props.tabRoutes?.[value];
    if (!targetRoute) {
        return;
    }

    router.get(targetRoute, {}, { preserveScroll: true, preserveState: false, replace: true });
};
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader :title="pageTitle" subtitle="" icon="mdi:settings-outline" />

        <div v-if="tabs?.length" class="max-w-md">
            <SelectButton
                v-model="selectedTab"
                :options="tabs"
                option-label="label"
                option-value="value"
                @update:model-value="switchTab"
            />
        </div>

        <SettingsCrudSection
            :title="sectionTitle"
            :description="description"
            :items="items"
            :route-base="routeBase"
            :with-color="withColor"
            :with-icon="withIcon"
            :with-order="withOrder"
            :reorder-only="reorderOnly"
            :disable-delete-when="disableDeleteWhen"
            :disable-delete-names="disableDeleteNames"
            :disable-delete-message="disableDeleteMessage"
            :reorder-route="reorderRoute"
            :extra-payload="extraPayload"
            :read-only="readOnly"
        />
    </div>
</template>
