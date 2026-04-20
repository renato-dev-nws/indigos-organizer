<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFormSection from '@/Components/ui/BoFormSection.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import {
    composePhoneWithCountryCode,
    formatBrazilPhoneInput,
    normalizeCountryCodeInput,
} from '@/Utils/phone';

defineOptions({ layout: AppLayout });

const goBack = () => {
    if (typeof window !== 'undefined' && window.history.length > 1) {
        window.history.back();
    }
};

defineProps({
    sizes: Array,
    types: Array,
    categories: Array,
    styles: Array,
});

const form = useForm({
    name: '',
    email: '',
    phone: '',
    contact_name: '',
    venue_size_id: null,
    venue_type_id: null,
    venue_category_id: null,
    venue_style_ids: [],
    place_id: '',
    address_line: '',
    address_number: '',
    address_complement: '',
    neighborhood: '',
    city: '',
    state: '',
    postal_code: '',
    country: '',
    latitude: null,
    longitude: null,
    status: 'undefined',
    performances_count: 0,
    equipment_tags: [],
    rating: null,
    instagram_url: '',
    facebook_url: '',
    youtube_url: '',
    whatsapp: '',
    website_url: '',
    notes: '',
    description: '',
});

const mapsApiKey = String(import.meta.env.VITE_GOOGLE_MAPS_API_KEY || '').trim();
const mapsApiKeyConfigured = mapsApiKey.length > 0;
const addressSearch = ref('');
const addressInput = ref(null);
const hasPlacesApi = ref(mapsApiKeyConfigured);
const manualAddressMode = ref(!mapsApiKeyConfigured);
const autocompleteInstance = ref(null);
const autocompleteInputEl = ref(null);
const phoneCountryCode = ref('55');
const whatsappCountryCode = ref('55');
let placesScriptPromise = null;

const canUseAutocomplete = computed(() => hasPlacesApi.value && !manualAddressMode.value);
const showAddressCard = computed(() => canUseAutocomplete.value && !!form.place_id);
const hasCoordinates = computed(() => form.latitude !== null && form.latitude !== '' && form.longitude !== null && form.longitude !== '');
const hasManualAddressForMap = computed(() => [form.address_line, form.city, form.state, form.country]
    .every((value) => String(value || '').trim().length > 0));

const readAddressComponent = (components, wantedType, shortName = false) => {
    const component = components.find((item) => item.types?.includes(wantedType));
    if (!component) {
        return '';
    }

    return shortName ? component.short_name : component.long_name;
};

const applyPlace = (place) => {
    const components = place.address_components || [];
    const route = readAddressComponent(components, 'route');
    const streetNumber = readAddressComponent(components, 'street_number');

    form.place_id = place.place_id || '';
    form.address_line = route || place.name || '';
    form.address_number = streetNumber;
    form.address_complement = '';
    form.neighborhood = readAddressComponent(components, 'sublocality') || readAddressComponent(components, 'neighborhood');
    form.city = readAddressComponent(components, 'administrative_area_level_2') || readAddressComponent(components, 'locality');
    form.state = readAddressComponent(components, 'administrative_area_level_1', true);
    form.postal_code = readAddressComponent(components, 'postal_code');
    form.country = readAddressComponent(components, 'country');
    form.latitude = place.geometry?.location?.lat?.() ?? null;
    form.longitude = place.geometry?.location?.lng?.() ?? null;

    const addressChunks = [
        route || place.name,
        streetNumber,
        form.neighborhood,
        form.city,
        form.state,
    ].filter(Boolean);
    addressSearch.value = addressChunks.join(', ');
};

const clearAddressSelection = () => {
    addressSearch.value = '';
    form.place_id = '';
    form.address_line = '';
    form.address_number = '';
    form.address_complement = '';
    form.neighborhood = '';
    form.city = '';
    form.state = '';
    form.postal_code = '';
    form.country = '';
    form.latitude = null;
    form.longitude = null;
};

const handleManualAddressToggle = (value) => {
    manualAddressMode.value = !!value;

    if (manualAddressMode.value) {
        form.place_id = '';
        return;
    }

    initAutocomplete();
};

const mapEmbedUrl = computed(() => {
    if (hasCoordinates.value) {
        return `https://maps.google.com/maps?q=${form.latitude},${form.longitude}&z=15&output=embed`;
    }

    const canRenderFromAddress = manualAddressMode.value
        ? hasManualAddressForMap.value
        : !!form.place_id;

    if (!canRenderFromAddress) {
        return '';
    }

    const query = [
        form.address_line,
        form.address_number,
        form.address_complement,
        form.neighborhood,
        [form.city, form.state].filter(Boolean).join('/'),
        form.postal_code,
        form.country,
    ].filter(Boolean).join(', ');

    if (query) {
        return `https://maps.google.com/maps?q=${encodeURIComponent(query)}&z=14&output=embed`;
    }

    return '';
});

const shouldShowMap = computed(() => {
    if (manualAddressMode.value) {
        return !!mapEmbedUrl.value;
    }

    return !!form.place_id && !!mapEmbedUrl.value;
});

const mapEmptyStateMessage = computed(() => {
    if (manualAddressMode.value) {
        return 'Preencha Logradouro, Cidade, UF e Pais para visualizar o mapa.';
    }

    return 'Selecione um endereco no autocomplete para visualizar o mapa.';
});

const loadGooglePlaces = async () => {
    if (window.google?.maps?.places) {
        return true;
    }

    if (!mapsApiKeyConfigured) {
        return false;
    }

    try {
        if (!placesScriptPromise) {
            placesScriptPromise = new Promise((resolve, reject) => {
                const existingScript = document.querySelector('script[data-google-places="1"]');
                if (existingScript) {
                    existingScript.addEventListener('load', resolve, { once: true });
                    existingScript.addEventListener('error', reject, { once: true });
                    return;
                }

                const script = document.createElement('script');
                script.dataset.googlePlaces = '1';
                script.src = `https://maps.googleapis.com/maps/api/js?key=${mapsApiKey}&libraries=places&loading=async`;
                script.async = true;
                script.defer = true;
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        }

        await placesScriptPromise;
    } catch (error) {
        placesScriptPromise = null;
        return false;
    }

    return !!window.google?.maps?.places;
};

const resolveAddressInputElement = () => {
    const fromRef = addressInput.value?.$el?.querySelector?.('input');
    if (fromRef instanceof HTMLInputElement) {
        return fromRef;
    }

    const fromDom = document.getElementById('venue-address-search');
    if (fromDom instanceof HTMLInputElement) {
        return fromDom;
    }

    return null;
};

const initAutocomplete = async () => {
    if (!canUseAutocomplete.value) {
        return;
    }

    await nextTick();
    const inputEl = resolveAddressInputElement();
    if (!inputEl) {
        return;
    }

    const ready = await loadGooglePlaces();
    if (!ready) {
        return;
    }

    if (autocompleteInputEl.value === inputEl && autocompleteInstance.value) {
        return;
    }

    autocompleteInputEl.value = inputEl;
    autocompleteInstance.value = new window.google.maps.places.Autocomplete(inputEl, {
        fields: ['address_components', 'formatted_address', 'geometry', 'name', 'place_id'],
        componentRestrictions: { country: 'br' },
    });

    autocompleteInstance.value.addListener('place_changed', () => {
        const place = autocompleteInstance.value.getPlace();
        if (place) {
            applyPlace(place);
        }
    });
};

onMounted(() => {
    if (mapsApiKeyConfigured) {
        initAutocomplete();
    }
});

watch(canUseAutocomplete, (enabled) => {
    if (enabled) {
        initAutocomplete();
    }
});

const updatePhone = (value) => {
    form.phone = formatBrazilPhoneInput(value);
};

const updatePhoneCountryCode = (value) => {
    phoneCountryCode.value = normalizeCountryCodeInput(value);
};

const updateWhatsapp = (value) => {
    form.whatsapp = formatBrazilPhoneInput(value);
};

const updateWhatsappCountryCode = (value) => {
    whatsappCountryCode.value = normalizeCountryCodeInput(value);
};

const submit = () => form
    .transform((data) => ({
        ...data,
        phone: composePhoneWithCountryCode(phoneCountryCode.value, data.phone),
        whatsapp: composePhoneWithCountryCode(whatsappCountryCode.value, data.whatsapp),
    }))
    .post(route('venues.store'));
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Novo local" supratitle="LOCAIS" subtitle="" icon="mdi:add-circle-outline">
            <template #actions>
                <div>
                    <Button type="button" class="!hidden md:!inline-flex" label="Voltar" icon="pi pi-arrow-left" outlined severity="secondary" @click="goBack" />
                    <Button type="button" class="!inline-flex md:!hidden" icon="pi pi-arrow-left" rounded outlined severity="secondary" aria-label="Voltar" @click="goBack" />
                </div>
            </template>
        </BoPageHeader>

        <form class="space-y-4" @submit.prevent="submit">
            <BoFormSection title="Informacoes gerais" description="Dados de identificacao e contato">
                <div class="space-y-2">
                    <label for="venue-name">Nome</label>
                    <InputText id="venue-name" v-model="form.name" fluid :invalid="!!form.errors.name" />
                    <Message v-if="form.errors.name" severity="error" size="small" variant="simple">{{ form.errors.name }}</Message>
                </div>

                <div class="space-y-2">
                    <label for="venue-type">Tipo</label>
                    <Select id="venue-type" v-model="form.venue_type_id" :options="types" option-label="name" option-value="id" show-clear fluid />
                </div>

                <div class="space-y-2">
                    <label for="venue-category">Categoria</label>
                    <Select id="venue-category" v-model="form.venue_category_id" :options="categories" option-label="name" option-value="id" show-clear fluid />
                </div>

                <div class="space-y-2">
                    <label for="venue-style">Estilo</label>
                    <MultiSelect id="venue-style" v-model="form.venue_style_ids" :options="styles" option-label="name" option-value="id" display="chip" placeholder="Selecione estilos" fluid />
                </div>

                <div class="space-y-2">
                    <label for="venue-contact">Contato</label>
                    <InputText id="venue-contact" v-model="form.contact_name" fluid />
                </div>

                <div class="space-y-2">
                    <label for="venue-phone">Telefone</label>
                    <InputGroup>
                        <InputGroupAddon>+</InputGroupAddon>
                        <InputText
                            :model-value="phoneCountryCode"
                            style="width: 54px; min-width: 54px; max-width: 54px"
                            inputmode="numeric"
                            @update:model-value="updatePhoneCountryCode"
                        />
                        <InputText id="venue-phone" :model-value="form.phone" placeholder="(11) 3456-7890" fluid @update:model-value="updatePhone" />
                    </InputGroup>
                </div>

                <div class="space-y-2">
                    <label for="venue-email">Email</label>
                    <InputText id="venue-email" v-model="form.email" fluid />
                </div>

                <div class="space-y-2">
                    <label for="venue-whatsapp">WhatsApp</label>
                    <InputGroup>
                        <InputGroupAddon>+</InputGroupAddon>
                        <InputText
                            :model-value="whatsappCountryCode"
                            style="width: 54px; min-width: 54px; max-width: 54px"
                            inputmode="numeric"
                            @update:model-value="updateWhatsappCountryCode"
                        />
                        <InputText id="venue-whatsapp" :model-value="form.whatsapp" placeholder="(11) 98765-4321" fluid @update:model-value="updateWhatsapp" />
                    </InputGroup>
                </div>

                <div class="space-y-2">
                    <label for="venue-status">Status</label>
                    <Select
                        id="venue-status"
                        v-model="form.status"
                        :options="[
                            { label: 'Indefinido', value: 'undefined' },
                            { label: 'Não relevante', value: 'not_relevant' },
                            { label: 'Contatado', value: 'contacted' },
                            { label: 'Vetado', value: 'vetoed' },
                            { label: 'Em negociação', value: 'negotiating' },
                            { label: 'Portas abertas', value: 'open_doors' },
                        ]"
                        option-label="label"
                        option-value="value"
                        fluid
                    />
                </div>

                <div class="space-y-2">
                    <label for="venue-performances">Vezes que se apresentou</label>
                    <InputNumber id="venue-performances" v-model="form.performances_count" :min="0" fluid />
                </div>

                <div class="space-y-2">
                    <label for="venue-rating" class="block">Avaliação</label>
                    <Rating id="venue-rating" v-model="form.rating" :cancel="true" />
                </div>

                <div class="space-y-2">
                    <label for="venue-equipment" class="block">Equipamentos</label>
                    <Chips id="venue-equipment" v-model="form.equipment_tags" separator="," fluid class="w-full" />
                </div>

                <div class="space-y-2 md:col-span-2">
                    <div class="mb-1 flex flex-wrap items-center justify-between gap-3">
                        <label for="venue-address-search">Endereço</label>
                        <div v-if="hasPlacesApi" class="flex items-center gap-2">
                            <Checkbox
                                input-id="venue-address-manual"
                                :model-value="manualAddressMode"
                                binary
                                @update:model-value="handleManualAddressToggle"
                            />
                            <label for="venue-address-manual" class="text-sm text-slate-600 dark:text-slate-300">Preenchimento manual</label>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 md:items-stretch">
                        <div class="space-y-3">
                            <div v-if="canUseAutocomplete" class="relative">
                                <InputText id="venue-address-search" ref="addressInput" v-model="addressSearch" placeholder="Digite e selecione um endereço" fluid @focus="initAutocomplete" />
                                <button
                                    v-if="addressSearch || form.place_id"
                                    type="button"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                    aria-label="Limpar endereço"
                                    @click="clearAddressSelection"
                                >
                                    <i class="pi pi-times-circle" />
                                </button>
                            </div>

                            <div v-if="manualAddressMode || !hasPlacesApi" class="grid gap-2 md:grid-cols-2">
                                <InputText class="md:col-span-2" v-model="form.address_line" placeholder="Logradouro (Linha 1)" />
                                <InputText v-model="form.address_number" placeholder="Número" />
                                <InputText v-model="form.address_complement" placeholder="Complemento" />
                                <InputText class="md:col-span-2" v-model="form.neighborhood" placeholder="Bairro" />
                                <InputText v-model="form.city" placeholder="Cidade" />
                                <InputText v-model="form.state" placeholder="UF" />
                                <InputText v-model="form.postal_code" placeholder="CEP" />
                                <InputText v-model="form.country" placeholder="País" />
                                <InputNumber v-if="manualAddressMode" v-model="form.latitude" :min-fraction-digits="4" :max-fraction-digits="7" placeholder="Latitude" fluid />
                                <InputNumber v-if="manualAddressMode" v-model="form.longitude" :min-fraction-digits="4" :max-fraction-digits="7" placeholder="Longitude" fluid />
                            </div>

                            <div v-else-if="showAddressCard" class="space-y-2 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm dark:border-slate-800 dark:bg-slate-900">
                                <p class="font-semibold">Endereço selecionado</p>
                                <div class="grid gap-2 md:grid-cols-2">
                                    <p><strong>Logradouro:</strong> {{ form.address_line || '-' }}</p>
                                    <p><strong>Número:</strong> {{ form.address_number || '-' }}</p>
                                    <p><strong>Bairro:</strong> {{ form.neighborhood || '-' }}</p>
                                    <p><strong>Cidade:</strong> {{ form.city || '-' }}</p>
                                    <p><strong>UF:</strong> {{ form.state || '-' }}</p>
                                    <p><strong>CEP:</strong> {{ form.postal_code || '-' }}</p>
                                    <p><strong>País:</strong> {{ form.country || '-' }}</p>
                                    <p><strong>Latitude/Longitude:</strong> {{ form.latitude || '-' }} / {{ form.longitude || '-' }}</p>
                                </div>
                                <div class="space-y-2">
                                    <label for="venue-autocomplete-complement">Complemento</label>
                                    <InputText id="venue-autocomplete-complement" v-model="form.address_complement" placeholder="Bloco, sala, etc. (opcional)" fluid />
                                </div>
                            </div>

                            <small v-if="!hasPlacesApi" class="text-slate-500">Sem API do Maps configurada. Use o preenchimento manual.</small>
                            <small v-else class="text-slate-500">Ao selecionar um endereço, cidade, estado, CEP e coordenadas são preenchidos automaticamente.</small>
                        </div>

                        <div class="h-full">
                            <div v-if="shouldShowMap" class="h-full overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700">
                                <iframe
                                    :src="mapEmbedUrl"
                                    class="h-full min-h-[24rem] w-full"
                                    loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade"
                                />
                            </div>
                            <div v-else class="flex h-full min-h-[24rem] flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 px-4 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                                <iconify-icon icon="mdi:map-marker-off-outline" width="28" height="28" class="mb-2 text-slate-400 dark:text-slate-500" />
                                <p>{{ mapEmptyStateMessage }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="venue-instagram">Instagram</label>
                    <InputText id="venue-instagram" v-model="form.instagram_url" fluid />
                </div>

                <div class="space-y-2">
                    <label for="venue-facebook">Facebook</label>
                    <InputText id="venue-facebook" v-model="form.facebook_url" fluid />
                </div>

                <div class="space-y-2">
                    <label for="venue-youtube">YouTube</label>
                    <InputText id="venue-youtube" v-model="form.youtube_url" fluid />
                </div>

                <div class="space-y-2">
                    <label for="venue-site">Website</label>
                    <InputText id="venue-site" v-model="form.website_url" fluid />
                </div>

                <div class="md:col-span-2 space-y-2">
                    <label for="venue-description">Descricao</label>
                    <Textarea id="venue-description" v-model="form.description" rows="4" fluid />
                </div>

                <div class="md:col-span-2 space-y-2">
                    <label for="venue-notes">Observacoes</label>
                    <Textarea id="venue-notes" v-model="form.notes" rows="3" fluid />
                </div>
            </BoFormSection>

            <div class="flex justify-end gap-2">
                <Link :href="route('venues.index')">
                    <Button type="button" label="Cancelar" outlined severity="secondary" />
                </Link>
                <Button type="submit" :loading="form.processing" label="Salvar local" icon="pi pi-save" />
            </div>
        </form>
    </div>
</template>
