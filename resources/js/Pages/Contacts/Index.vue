<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFilterBar from '@/Components/ui/BoFilterBar.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoDataTableEmpty from '@/Components/ui/BoDataTableEmpty.vue';
import BoConfirmButton from '@/Components/ui/BoConfirmButton.vue';
import ContactFormModal from '@/Components/contacts/ContactFormModal.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    contacts: Object,
    venues: Array,
    filters: Object,
});

const localFilters = reactive({
    search: props.filters?.search ?? '',
    venue_id: props.filters?.venue_id ?? null,
});

const showFormModal = ref(false);
const selectedContact = ref(null);

const syncLocalFiltersFromProps = () => {
    localFilters.search = props.filters?.search ?? '';
    localFilters.venue_id = props.filters?.venue_id ?? null;
};

watch(() => props.filters, syncLocalFiltersFromProps, { deep: true });

const filterChips = computed(() => {
    const chips = [];

    if (localFilters.search) {
        chips.push({ key: 'search', label: localFilters.search });
    }

    if (localFilters.venue_id) {
        const venue = props.venues?.find((item) => item.id === localFilters.venue_id);
        if (venue) {
            chips.push({ key: 'venue_id', label: venue.name });
        }
    }

    return chips;
});

const submitFilters = () => {
    router.get(route('contacts.index'), localFilters, { preserveState: true, preserveScroll: true, replace: true });
};

const resetFilters = () => {
    localFilters.search = '';
    localFilters.venue_id = null;
    submitFilters();
};

const removeChip = (key) => {
    localFilters[key] = key === 'search' ? '' : null;
    submitFilters();
};

const cancelFilters = () => {
    syncLocalFiltersFromProps();
};

const paginate = (event) => {
    router.get(route('contacts.index'), { ...localFilters, page: event.page + 1 }, { preserveState: true, preserveScroll: true, replace: true });
};

const openCreateModal = () => {
    selectedContact.value = null;
    showFormModal.value = true;
};

const openEditModal = (contact) => {
    selectedContact.value = contact;
    showFormModal.value = true;
};

const removeContact = (contactId) => router.delete(route('contacts.destroy', contactId), { preserveScroll: true });

const refreshContacts = () => {
    router.reload({ only: ['contacts'], preserveScroll: true });
};
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Contatos" subtitle="Base de contatos da banda e de locais">
            <template #actions>
                <Button class="!hidden md:!inline-flex" icon="pi pi-plus" label="Novo contato" @click="openCreateModal" />
                <Button class="!inline-flex md:!hidden" icon="pi pi-plus" rounded aria-label="Novo contato" @click="openCreateModal" />
            </template>
        </BoPageHeader>

        <BoFilterBar :chips="filterChips" @submit="submitFilters" @reset="resetFilters" @remove-chip="removeChip" @cancel="cancelFilters">
            <div class="space-y-2">
                <label class="text-sm font-medium">Busca</label>
                <InputText v-model="localFilters.search" placeholder="Nome, email, telefone ou WhatsApp" />
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium">Local relacionado</label>
                <Select v-model="localFilters.venue_id" :options="venues" option-label="name" option-value="id" show-clear placeholder="Todos" />
            </div>
        </BoFilterBar>

        <Card>
            <template #content>
                <div class="hidden md:block">
                    <DataTable :value="contacts.data" data-key="id" striped-rows>
                        <Column field="name" header="Nome" />
                        <Column field="phone" header="Telefone" />
                        <Column field="whatsapp" header="WhatsApp" />
                        <Column field="email" header="Email" />
                        <Column header="Local">
                            <template #body="{ data }">{{ data.venue?.name || '-' }}</template>
                        </Column>
                        <Column header="Descrição">
                            <template #body="{ data }">
                                <span class="line-clamp-2 text-sm text-slate-600 dark:text-slate-300">{{ data.description || '-' }}</span>
                            </template>
                        </Column>
                        <Column header="Ações" class="bo-action-col w-24">
                            <template #body="{ data }">
                                <div class="flex gap-1">
                                    <Button icon="pi pi-pencil" size="small" outlined rounded severity="secondary" @click="openEditModal(data)" />
                                    <BoConfirmButton icon="pi pi-trash" severity="danger" message="Deseja remover este contato?" :rounded="true" @confirm="removeContact(data.id)" />
                                </div>
                            </template>
                        </Column>
                        <template #empty>
                            <BoDataTableEmpty />
                        </template>
                    </DataTable>
                </div>

                <div class="block space-y-3 md:hidden">
                    <div v-for="contact in contacts.data" :key="contact.id" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <h3 class="font-semibold">{{ contact.name }}</h3>
                        <p class="mt-1 text-xs text-slate-500">Telefone: {{ contact.phone || '-' }}</p>
                        <p class="text-xs text-slate-500">WhatsApp: {{ contact.whatsapp || '-' }}</p>
                        <p class="text-xs text-slate-500">Email: {{ contact.email || '-' }}</p>
                        <p class="text-xs text-slate-500">Local: {{ contact.venue?.name || '-' }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ contact.description || 'Sem descrição.' }}</p>
                        <div class="mt-3 flex justify-end gap-1">
                            <Button icon="pi pi-pencil" size="small" outlined rounded severity="secondary" @click="openEditModal(contact)" />
                            <BoConfirmButton icon="pi pi-trash" severity="danger" message="Deseja remover este contato?" :rounded="true" @confirm="removeContact(contact.id)" />
                        </div>
                    </div>
                </div>

                <Paginator
                    class="mt-4"
                    :rows="contacts.per_page"
                    :total-records="contacts.total"
                    :first="(contacts.current_page - 1) * contacts.per_page"
                    @page="paginate"
                />
            </template>
        </Card>

        <ContactFormModal v-model:visible="showFormModal" :contact="selectedContact" :venues="venues" @saved="refreshContacts" />
    </div>
</template>
