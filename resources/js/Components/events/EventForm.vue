<script setup>
import axios from 'axios';
import { computed, nextTick, reactive, ref, watch } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
    types: { type: Array, default: () => [] },
    venues: { type: Array, default: () => [] },
    venueTypes: { type: Array, default: () => [] },
    venueCategories: { type: Array, default: () => [] },
    venueStyles: { type: Array, default: () => [] },
    submitLabel: { type: String, default: 'Salvar evento' },
    cancelHref: { type: String, required: true },
});

const emit = defineEmits(['submit']);

const venueOptions = ref([...(props.venues || [])]);
const venueDialogVisible = ref(false);
const venueAddressInput = ref(null);
const venueAddressSearch = ref('');
const mapCanvas = ref(null);

let googleMapsPromise = null;
let previewMap = null;
let previewMarker = null;

const quickVenueForm = reactive({
    name: '',
    venue_type_id: null,
    venue_category_id: null,
    venue_style_id: null,
    place_id: '',
    address_line: '',
    address_number: '',
    neighborhood: '',
    city: '',
    state: '',
    postal_code: '',
    country: '',
    latitude: null,
    longitude: null,
    status: 'undefined',
});

const quickVenueErrors = reactive({
    name: '',
    address: '',
    submit: '',
});

watch(
    () => props.venues,
    (value) => {
        venueOptions.value = [...(value || [])];
    },
    { deep: true },
);

const selectedVenue = computed(() => venueOptions.value.find((venue) => venue.id === props.form.venue_id) || null);

const isDarkMode = () => typeof document !== 'undefined'
    && (
        document.documentElement.classList.contains('app-dark')
        || document.documentElement.classList.contains('dark')
    );

const mapStyles = [
    { elementType: 'geometry', stylers: [{ color: '#0f172a' }] },
    { elementType: 'labels.text.fill', stylers: [{ color: '#94a3b8' }] },
    { elementType: 'labels.text.stroke', stylers: [{ color: '#0b1220' }] },
    { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#1e293b' }] },
    { featureType: 'road', elementType: 'geometry.stroke', stylers: [{ color: '#0f172a' }] },
    { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#082f49' }] },
];

const clearQuickVenueErrors = () => {
    quickVenueErrors.name = '';
    quickVenueErrors.address = '';
    quickVenueErrors.submit = '';
};

const resetQuickVenueForm = () => {
    quickVenueForm.name = '';
    quickVenueForm.venue_type_id = null;
    quickVenueForm.venue_category_id = null;
    quickVenueForm.venue_style_id = null;
    quickVenueForm.place_id = '';
    quickVenueForm.address_line = '';
    quickVenueForm.address_number = '';
    quickVenueForm.neighborhood = '';
    quickVenueForm.city = '';
    quickVenueForm.state = '';
    quickVenueForm.postal_code = '';
    quickVenueForm.country = '';
    quickVenueForm.latitude = null;
    quickVenueForm.longitude = null;
    quickVenueForm.status = 'undefined';
    venueAddressSearch.value = '';
    clearQuickVenueErrors();
};

const addExtraInfo = () => {
    props.form.extra_infos.push({ title: '', information: '' });
};

const removeExtraInfo = (index) => {
    props.form.extra_infos.splice(index, 1);
};

const addLink = () => {
    props.form.links.push({ title: '', url: '' });
};

const removeLink = (index) => {
    props.form.links.splice(index, 1);
};

const ensureGoogleMaps = async () => {
    if (window.google?.maps) {
        return true;
    }

    if (googleMapsPromise) {
        return googleMapsPromise;
    }

    const key = import.meta.env.VITE_GOOGLE_MAPS_API_KEY;
    if (!key) {
        return false;
    }

    googleMapsPromise = new Promise((resolve, reject) => {
        const existingScript = document.querySelector('script[data-bo-google-maps="true"]');
        if (existingScript) {
            existingScript.addEventListener('load', () => resolve(true), { once: true });
            existingScript.addEventListener('error', reject, { once: true });
            return;
        }

        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=${key}&libraries=places&loading=async`;
        script.async = true;
        script.defer = true;
        script.dataset.boGoogleMaps = 'true';
        script.onload = () => resolve(true);
        script.onerror = reject;
        document.head.appendChild(script);
    }).catch(() => false);

    return googleMapsPromise;
};

const readAddressComponent = (components, wantedType, shortName = false) => {
    const component = (components || []).find((item) => item.types?.includes(wantedType));
    if (!component) {
        return '';
    }

    return shortName ? component.short_name : component.long_name;
};

const applyQuickVenuePlace = (place) => {
    const components = place.address_components || [];
    const route = readAddressComponent(components, 'route');
    const streetNumber = readAddressComponent(components, 'street_number');

    quickVenueForm.place_id = place.place_id || '';
    quickVenueForm.address_line = route || place.name || '';
    quickVenueForm.address_number = streetNumber;
    quickVenueForm.neighborhood = readAddressComponent(components, 'sublocality') || readAddressComponent(components, 'neighborhood');
    quickVenueForm.city = readAddressComponent(components, 'administrative_area_level_2') || readAddressComponent(components, 'locality');
    quickVenueForm.state = readAddressComponent(components, 'administrative_area_level_1', true);
    quickVenueForm.postal_code = readAddressComponent(components, 'postal_code');
    quickVenueForm.country = readAddressComponent(components, 'country');
    quickVenueForm.latitude = place.geometry?.location?.lat?.() ?? null;
    quickVenueForm.longitude = place.geometry?.location?.lng?.() ?? null;

    venueAddressSearch.value = [
        route || place.name,
        streetNumber,
        quickVenueForm.neighborhood,
        quickVenueForm.city,
        quickVenueForm.state,
    ].filter(Boolean).join(', ');
};

const initQuickVenueAutocomplete = async () => {
    await nextTick();

    if (!venueAddressInput.value) {
        return;
    }

    const ready = await ensureGoogleMaps();
    if (!ready || !window.google?.maps?.places) {
        return;
    }

    const target = venueAddressInput.value.$el?.querySelector('input') || venueAddressInput.value.$el || venueAddressInput.value;
    const autocomplete = new window.google.maps.places.Autocomplete(target, {
        fields: ['address_components', 'formatted_address', 'geometry', 'name', 'place_id'],
        componentRestrictions: { country: 'br' },
    });

    autocomplete.addListener('place_changed', () => {
        const place = autocomplete.getPlace();
        if (place) {
            applyQuickVenuePlace(place);
        }
    });
};

const renderMap = async () => {
    if (!mapCanvas.value) {
        return;
    }

    const venue = selectedVenue.value;
    const lat = Number(venue?.latitude);
    const lng = Number(venue?.longitude);
    if (!venue || !Number.isFinite(lat) || !Number.isFinite(lng)) {
        previewMap = null;
        previewMarker = null;
        return;
    }

    const ready = await ensureGoogleMaps();
    if (!ready || !window.google?.maps) {
        return;
    }

    const position = { lat, lng };

    if (!previewMap) {
        previewMap = new window.google.maps.Map(mapCanvas.value, {
            center: position,
            zoom: 15,
            styles: isDarkMode() ? mapStyles : null,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false,
        });
        previewMarker = new window.google.maps.Marker({
            map: previewMap,
            position,
        });
        return;
    }

    previewMap.setOptions({ styles: isDarkMode() ? mapStyles : null });
    previewMap.setCenter(position);
    previewMarker?.setPosition(position);
  };

watch(selectedVenue, () => {
    renderMap();
}, { deep: true, immediate: true });

watch(venueDialogVisible, (value) => {
    if (value) {
        initQuickVenueAutocomplete();
        return;
    }

    resetQuickVenueForm();
});

const createVenue = async () => {
    clearQuickVenueErrors();

    if (!quickVenueForm.name.trim()) {
        quickVenueErrors.name = 'Nome é obrigatório.';
        return;
    }

    try {
        const { data } = await axios.post(route('venues.quick-store'), { ...quickVenueForm });
        const createdVenue = data.venue;
        if (!venueOptions.value.find((venue) => venue.id === createdVenue.id)) {
            venueOptions.value = [...venueOptions.value, createdVenue].sort((left, right) => left.name.localeCompare(right.name));
        }
        props.form.venue_id = createdVenue.id;
        venueDialogVisible.value = false;
    } catch (error) {
        const responseErrors = error?.response?.data?.errors || {};
        quickVenueErrors.name = responseErrors.name?.[0] || '';
        quickVenueErrors.address = responseErrors.address_line?.[0] || '';
        quickVenueErrors.submit = error?.response?.data?.message || 'Não foi possível cadastrar o local.';
    }
  };
</script>

<template>
    <form class="space-y-4" @submit.prevent="emit('submit')">
        <BoFormSection title="Dados principais" description="Informações do evento, agenda e ingressos">
            <div class="md:col-span-2 space-y-2">
                <label for="event-title">Título</label>
                <InputText id="event-title" v-model="form.title" fluid :invalid="!!form.errors.title" />
                <Message v-if="form.errors.title" severity="error" size="small" variant="simple">{{ form.errors.title }}</Message>
            </div>

            <div class="space-y-2">
                <label for="event-type">Tipo</label>
                <Select id="event-type" v-model="form.event_type_id" :options="types" option-label="name" option-value="id" show-clear fluid />
            </div>

            <div class="space-y-2">
                <label for="event-attendance">Presença</label>
                <Select
                    id="event-attendance"
                    v-model="form.attendance_mode"
                    :options="[
                        { label: 'Como participante', value: 'participant' },
                        { label: 'Como público', value: 'audience' },
                    ]"
                    option-label="label"
                    option-value="value"
                    fluid
                />
            </div>

            <div class="space-y-2">
                <label for="event-date">Data</label>
                <DatePicker id="event-date" v-model="form.event_date" fluid />
                <Message v-if="form.errors.event_date" severity="error" size="small" variant="simple">{{ form.errors.event_date }}</Message>
            </div>

            <div class="space-y-2">
                <label for="event-time">Hora</label>
                <InputMask id="event-time" v-model="form.event_time" mask="99:99" placeholder="20:30" fluid />
                <Message v-if="form.errors.event_time" severity="error" size="small" variant="simple">{{ form.errors.event_time }}</Message>
            </div>

            <div class="md:col-span-2 space-y-2">
                <label for="event-description">Descrição</label>
                <Textarea id="event-description" v-model="form.description" rows="5" fluid />
            </div>

            <div class="md:col-span-2 grid gap-4 xl:grid-cols-2">
                <div class="space-y-3 rounded-2xl border border-slate-200/80 p-4 dark:border-slate-800">
                    <div class="space-y-2">
                        <label for="event-venue">Local</label>
                        <InputGroup>
                            <Select id="event-venue" v-model="form.venue_id" :options="venueOptions" option-label="name" option-value="id" show-clear fluid placeholder="Selecione um local" />
                            <InputGroupAddon>
                                <Button type="button" icon="pi pi-plus" outlined severity="secondary" aria-label="Cadastro rápido de local" @click="venueDialogVisible = true" />
                            </InputGroupAddon>
                        </InputGroup>
                        <Message v-if="form.errors.venue_id" severity="error" size="small" variant="simple">{{ form.errors.venue_id }}</Message>
                    </div>

                    <div v-if="selectedVenue" class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm dark:border-slate-800 dark:bg-slate-900">
                        <p class="font-semibold text-slate-700 dark:text-slate-100">{{ selectedVenue.name }}</p>
                        <p class="mt-1 text-slate-500 dark:text-slate-400">{{ selectedVenue.address || 'Endereço não informado.' }}</p>
                        <div class="mt-2 flex flex-wrap gap-2 text-xs">
                            <Tag v-if="selectedVenue.type?.name" :value="selectedVenue.type.name" severity="secondary" />
                            <Tag v-if="selectedVenue.category?.name" :value="selectedVenue.category.name" severity="info" />
                            <Tag v-if="selectedVenue.style?.name" :value="selectedVenue.style.name" severity="contrast" />
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
                    <div v-if="selectedVenue && Number.isFinite(Number(selectedVenue.latitude)) && Number.isFinite(Number(selectedVenue.longitude))" ref="mapCanvas" class="h-full min-h-64 w-full" />
                    <div v-else class="flex min-h-64 items-center justify-center text-slate-400 dark:text-slate-500">
                        <div class="text-center">
                            <iconify-icon icon="ph:map-pin-area-bold" width="48" height="48" />
                            <p class="mt-3 text-sm">Selecione um local para visualizar o mapa.</p>
                        </div>
                    </div>
                </div>
            </div>
        </BoFormSection>

        <BoFormSection title="Ingressos" description="Links e valores por lote">
            <div class="md:col-span-2 space-y-2">
                <label for="ticket-link">Link de ingresso</label>
                <InputText id="ticket-link" v-model="form.ticket_link" placeholder="https://..." fluid />
            </div>
            <div class="space-y-2">
                <label for="ticket-first">1º lote</label>
                <InputNumber id="ticket-first" v-model="form.ticket_price_first_batch" mode="currency" currency="BRL" locale="pt-BR" fluid />
            </div>
            <div class="space-y-2">
                <label for="ticket-second">2º lote</label>
                <InputNumber id="ticket-second" v-model="form.ticket_price_second_batch" mode="currency" currency="BRL" locale="pt-BR" fluid />
            </div>
            <div class="space-y-2">
                <label for="ticket-third">3º lote</label>
                <InputNumber id="ticket-third" v-model="form.ticket_price_third_batch" mode="currency" currency="BRL" locale="pt-BR" fluid />
            </div>
            <div class="space-y-2">
                <label for="ticket-door">Na porta</label>
                <InputNumber id="ticket-door" v-model="form.ticket_price_door" mode="currency" currency="BRL" locale="pt-BR" fluid />
            </div>
        </BoFormSection>

        <Card>
            <template #title>Informações extras</template>
            <template #content>
                <div class="space-y-3">
                    <div v-for="(item, index) in form.extra_infos" :key="index" class="grid gap-3 rounded-xl border border-slate-200/80 p-3 md:grid-cols-[1fr_2fr_auto] dark:border-slate-800">
                        <InputText v-model="item.title" placeholder="Título" />
                        <Textarea v-model="item.information" rows="2" placeholder="Informação" fluid />
                        <div class="flex items-start justify-end">
                            <Button type="button" icon="pi pi-trash" text severity="danger" aria-label="Remover informação" @click="removeExtraInfo(index)" />
                        </div>
                    </div>

                    <Button type="button" icon="pi pi-plus" label="Adicionar informação" outlined @click="addExtraInfo" />
                </div>
            </template>
        </Card>

        <Card>
            <template #title>Outros links</template>
            <template #content>
                <div class="space-y-3">
                    <div v-for="(link, index) in form.links" :key="index" class="grid gap-3 rounded-xl border border-slate-200/80 p-3 md:grid-cols-[1fr_2fr_auto] dark:border-slate-800">
                        <InputText v-model="link.title" placeholder="Título" />
                        <InputText v-model="link.url" placeholder="https://..." />
                        <div class="flex items-start justify-end">
                            <Button type="button" icon="pi pi-trash" text severity="danger" aria-label="Remover link" @click="removeLink(index)" />
                        </div>
                    </div>

                    <Button type="button" icon="pi pi-plus" label="Adicionar link" outlined @click="addLink" />
                </div>
            </template>
        </Card>

        <div class="flex justify-end gap-2">
            <Link :href="cancelHref">
                <Button type="button" label="Cancelar" outlined severity="secondary" />
            </Link>
            <Button type="submit" :loading="form.processing" :label="submitLabel" icon="pi pi-save" />
        </div>
    </form>

    <Dialog v-model:visible="venueDialogVisible" modal header="Cadastro rápido de local" :style="{ width: '42rem', maxWidth: '96vw' }">
        <div class="space-y-4">
            <Message v-if="quickVenueErrors.submit" severity="error" size="small" variant="simple">{{ quickVenueErrors.submit }}</Message>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2 md:col-span-2">
                    <label for="quick-venue-name">Nome</label>
                    <InputText id="quick-venue-name" v-model="quickVenueForm.name" fluid :invalid="!!quickVenueErrors.name" />
                    <Message v-if="quickVenueErrors.name" severity="error" size="small" variant="simple">{{ quickVenueErrors.name }}</Message>
                </div>

                <div class="space-y-2">
                    <label for="quick-venue-type">Tipo</label>
                    <Select id="quick-venue-type" v-model="quickVenueForm.venue_type_id" :options="venueTypes" option-label="name" option-value="id" show-clear fluid />
                </div>

                <div class="space-y-2">
                    <label for="quick-venue-category">Categoria</label>
                    <Select id="quick-venue-category" v-model="quickVenueForm.venue_category_id" :options="venueCategories" option-label="name" option-value="id" show-clear fluid />
                </div>

                <div class="space-y-2 md:col-span-2">
                    <label for="quick-venue-style">Estilo</label>
                    <Select id="quick-venue-style" v-model="quickVenueForm.venue_style_id" :options="venueStyles" option-label="name" option-value="id" show-clear fluid />
                </div>

                <div class="space-y-2 md:col-span-2">
                    <label for="quick-venue-address">Endereço</label>
                    <InputText id="quick-venue-address" ref="venueAddressInput" v-model="venueAddressSearch" placeholder="Digite e selecione um endereço" fluid :invalid="!!quickVenueErrors.address" />
                    <small class="text-slate-500">O autocomplete do Google Maps preenche o endereço e as coordenadas.</small>
                    <Message v-if="quickVenueErrors.address" severity="error" size="small" variant="simple">{{ quickVenueErrors.address }}</Message>
                </div>
            </div>
        </div>

        <template #footer>
            <Button label="Cancelar" text @click="venueDialogVisible = false" />
            <Button label="Salvar local" icon="pi pi-save" @click="createVenue" />
        </template>
    </Dialog>
</template>