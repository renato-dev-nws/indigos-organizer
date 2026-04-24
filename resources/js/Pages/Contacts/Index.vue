<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFilterBar from '@/Components/ui/BoFilterBar.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoDataTableEmpty from '@/Components/ui/BoDataTableEmpty.vue';
import BoConfirmButton from '@/Components/ui/BoConfirmButton.vue';
import ContactFormModal from '@/Components/contacts/ContactFormModal.vue';
import { buildWhatsAppUrl, formatBrazilPhone } from '@/Utils/phone';

defineOptions({ layout: AppLayout });

const props = defineProps({
    contacts: Object,
    venues: Array,
    filters: Object,
});

const localFilters = reactive({
    name: props.filters?.name ?? '',
    search: props.filters?.search ?? '',
    venue_id: props.filters?.venue_id ?? null,
});
const quickSearch = ref(props.filters?.search ?? '');
const quickSearchTimer = ref(null);

const showFormModal = ref(false);
const selectedContact = ref(null);

const syncLocalFiltersFromProps = () => {
    localFilters.name = props.filters?.name ?? '';
    localFilters.search = props.filters?.search ?? '';
    localFilters.venue_id = props.filters?.venue_id ?? null;
    quickSearch.value = props.filters?.search ?? '';
};

watch(() => props.filters, syncLocalFiltersFromProps, { deep: true });

watch(quickSearch, (value) => {
    if (quickSearchTimer.value) {
        clearTimeout(quickSearchTimer.value);
    }

    quickSearchTimer.value = setTimeout(() => {
        if (localFilters.search === value) {
            return;
        }

        localFilters.search = value;
        submitFilters();
    }, 1000);
});

const filterChips = computed(() => {
    const chips = [];

    if (localFilters.name) {
        chips.push({ key: 'name', label: `Nome: ${localFilters.name}` });
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
    localFilters.name = '';
    localFilters.search = '';
    localFilters.venue_id = null;
    quickSearch.value = '';
    submitFilters();
};

const removeChip = (key) => {
    localFilters[key] = ['name', 'search'].includes(key) ? '' : null;
    if (key === 'search') {
        quickSearch.value = '';
    }
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

const whatsappUrl = (value) => buildWhatsAppUrl(value);
const phoneLabel = (value) => formatBrazilPhone(value) || '-';
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Contatos" subtitle="Base de contatos da banda e de locais" helper="contatos" icon="ph:address-book-bold">
            <template #actions>
                <Button class="!hidden md:!inline-flex" icon="pi pi-plus" label="Novo contato" @click="openCreateModal" />
                <Button class="!inline-flex md:!hidden" icon="pi pi-plus" rounded aria-label="Novo contato" @click="openCreateModal" />
            </template>
        </BoPageHeader>

        <BoFilterBar :chips="filterChips" @submit="submitFilters" @reset="resetFilters" @remove-chip="removeChip" @cancel="cancelFilters">
            <template #right-actions>
                <div class="relative">
                    <InputText v-model="quickSearch" class="w-72 pr-8" placeholder="Busca rápida de contatos" />
                    <button
                        v-if="quickSearch"
                        type="button"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                        aria-label="Limpar busca"
                        @click="quickSearch = ''"
                    >
                        <i class="pi pi-times-circle" />
                    </button>
                </div>
            </template>

            <div class="space-y-2">
                <label class="text-sm font-medium">Nome</label>
                <InputText v-model="localFilters.name" placeholder="Filtrar por nome" />
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium">Local relacionado</label>
                <Select v-model="localFilters.venue_id" :options="venues" option-label="name" option-value="id" show-clear filter placeholder="Todos" />
            </div>
        </BoFilterBar>

        <Card>
            <template #content>
                <div class="hidden md:block">
                    <DataTable :value="contacts.data" data-key="id" striped-rows>
                        <Column field="name" header="Nome" />
                        <Column header="Telefone">
                            <template #body="{ data }">{{ phoneLabel(data.phone) }}</template>
                        </Column>
                        <Column field="whatsapp" header="WhatsApp">
                            <template #body="{ data }">
                                <a v-if="whatsappUrl(data.whatsapp)" :href="whatsappUrl(data.whatsapp)" target="_blank" rel="noopener" class="text-emerald-600 underline dark:text-emerald-400">{{ phoneLabel(data.whatsapp) }}</a>
                                <span v-else>-</span>
                            </template>
                        </Column>
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

                <div v-if="contacts.data.length" class="block space-y-3 md:hidden">
                    <div v-for="contact in contacts.data" :key="contact.id" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="grid grid-cols-5 gap-3">
                            <div class="col-span-3 space-y-2">
                                <h3 class="text-base font-semibold leading-5">{{ contact.name }}</h3>

                                <div>
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Telefone</p>
                                    <p class="text-sm text-slate-700 dark:text-slate-200">{{ phoneLabel(contact.phone) }}</p>
                                </div>

                                <div>
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500">WhatsApp</p>
                                    <p class="text-sm text-slate-700 dark:text-slate-200">
                                        <a v-if="whatsappUrl(contact.whatsapp)" :href="whatsappUrl(contact.whatsapp)" target="_blank" rel="noopener" class="text-emerald-600 underline dark:text-emerald-400">{{ phoneLabel(contact.whatsapp) }}</a>
                                        <span v-else>-</span>
                                    </p>
                                </div>

                                <div>
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Email</p>
                                    <p class="text-sm text-slate-700 dark:text-slate-200">{{ contact.email || '-' }}</p>
                                </div>

                                <div>
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Local</p>
                                    <p class="text-sm text-slate-700 dark:text-slate-200">{{ contact.venue?.name || '-' }}</p>
                                </div>

                                <div>
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500">Descrição</p>
                                    <p class="text-xs text-slate-600 dark:text-slate-300">{{ contact.description || 'Sem descrição.' }}</p>
                                </div>
                            </div>

                            <div class="col-span-2 flex flex-col items-end justify-between gap-3">
                                <Tag :value="contact.venue?.name ? 'Com local' : 'Sem local'" severity="secondary" class="!px-1.5 !py-0.5" />

                                <div class="flex flex-wrap justify-end gap-1">
                                    <Button icon="pi pi-pencil" size="small" outlined rounded severity="secondary" @click="openEditModal(contact)" />
                                    <BoConfirmButton icon="pi pi-trash" severity="danger" message="Deseja remover este contato?" :rounded="true" @confirm="removeContact(contact.id)" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="md:hidden">
                    <div class="rounded-xl border border-dashed border-slate-300 bg-white p-6 text-center dark:border-slate-700 dark:bg-slate-900">
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Nenhum contato ainda.</p>
                        <Button class="mt-3" label="Crie seu primeiro contato" icon="pi pi-plus" size="small" @click="openCreateModal" />
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
