<script setup>
import { nextTick, onMounted, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFormSection from '@/Components/ui/BoFormSection.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';

const props = defineProps({ venue: Object, sizes: Array, types: Array, categories: Array, styles: Array });
defineOptions({ layout: AppLayout });

const form = useForm({
    name: props.venue.name,
    email: props.venue.email,
    phone: props.venue.phone,
    contact_name: props.venue.contact_name,
    venue_size_id: props.venue.venue_size_id,
    venue_type_id: props.venue.venue_type_id,
    venue_category_id: props.venue.venue_category_id,
    venue_style_id: props.venue.venue_style_id,
    place_id: props.venue.place_id,
    address_line: props.venue.address_line,
    address_number: props.venue.address_number,
    neighborhood: props.venue.neighborhood,
    city: props.venue.city,
    state: props.venue.state,
    postal_code: props.venue.postal_code,
    country: props.venue.country,
    latitude: props.venue.latitude,
    longitude: props.venue.longitude,
    status: props.venue.status,
    performances_count: props.venue.performances_count,
    equipment_tags: props.venue.equipment_tags ?? [],
    rating: props.venue.rating,
    instagram_url: props.venue.instagram_url,
    facebook_url: props.venue.facebook_url,
    youtube_url: props.venue.youtube_url,
    whatsapp: props.venue.whatsapp,
    website_url: props.venue.website_url,
    notes: props.venue.notes,
    description: props.venue.description,
});

const addressSearch = ref([
    props.venue.address_line,
    props.venue.address_number,
    props.venue.neighborhood,
    props.venue.city,
    props.venue.state,
]
    .filter(Boolean)
    .join(', '));
const addressInput = ref(null);

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

const loadGooglePlaces = async () => {
    if (window.google?.maps?.places) {
        return true;
    }

    const key = import.meta.env.VITE_GOOGLE_MAPS_API_KEY;
    if (!key) {
        return false;
    }

    await new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=${key}&libraries=places&loading=async`;
        script.async = true;
        script.defer = true;
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
    });

    return !!window.google?.maps?.places;
};

const initAutocomplete = async () => {
    await nextTick();
    if (!addressInput.value) {
        return;
    }

    const ready = await loadGooglePlaces();
    if (!ready) {
        return;
    }

    const autocomplete = new window.google.maps.places.Autocomplete(addressInput.value.$el?.querySelector('input') || addressInput.value.$el || addressInput.value, {
        fields: ['address_components', 'formatted_address', 'geometry', 'name', 'place_id'],
        componentRestrictions: { country: 'br' },
    });

    autocomplete.addListener('place_changed', () => {
        const place = autocomplete.getPlace();
        if (place) {
            applyPlace(place);
        }
    });
};

onMounted(() => {
    initAutocomplete();
});

const submit = () => form.put(route('venues.update', props.venue.id));
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Editar local" supratitle="LOCAIS" subtitle="" icon="mdi:circle-edit-outline">
            <template #actions>
                <Link :href="route('venues.show', venue.id)">
                    <Button class="!hidden md:!inline-flex" label="Visualizar" icon="pi pi-eye" outlined severity="secondary" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-eye" rounded outlined severity="secondary" aria-label="Visualizar" />
                </Link>
            </template>
        </BoPageHeader>

        <form class="space-y-4" @submit.prevent="submit">
            <BoFormSection title="Informacoes gerais" description="Dados de identificacao e contato">
                <div class="space-y-2">
                    <label for="venue-name">Nome</label>
                    <InputText id="venue-name" v-model="form.name" fluid :invalid="!!form.errors.name" />
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
                    <Select id="venue-style" v-model="form.venue_style_id" :options="styles" option-label="name" option-value="id" show-clear fluid />
                </div>

                <div class="space-y-2">
                    <label for="venue-contact">Contato</label>
                    <InputText id="venue-contact" v-model="form.contact_name" fluid />
                </div>

                <div class="space-y-2">
                    <label for="venue-phone">Telefone</label>
                    <InputText id="venue-phone" v-model="form.phone" fluid />
                </div>

                <div class="space-y-2">
                    <label for="venue-email">Email</label>
                    <InputText id="venue-email" v-model="form.email" fluid />
                </div>

                <div class="space-y-2">
                    <label for="venue-whatsapp">WhatsApp</label>
                    <InputText id="venue-whatsapp" v-model="form.whatsapp" fluid />
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
                    <label for="venue-performances">Vezes que já tocou</label>
                    <InputNumber id="venue-performances" v-model="form.performances_count" :min="0" fluid />
                </div>

                <div class="space-y-2">
                    <label for="venue-rating">Avaliação</label>
                    <Rating id="venue-rating" v-model="form.rating" :cancel="true" />
                </div>

                <div class="space-y-2">
                    <label for="venue-equipment">Equipamentos</label>
                    <Chips id="venue-equipment" v-model="form.equipment_tags" separator="," fluid />
                </div>

                <div class="space-y-2 md:col-span-2">
                    <label for="venue-address-search">Endereço</label>
                    <InputText id="venue-address-search" ref="addressInput" v-model="addressSearch" placeholder="Digite e selecione um endereço" fluid />
                    <small class="text-slate-500">Ao selecionar um endereço, cidade, estado, CEP e coordenadas são preenchidos automaticamente.</small>
                </div>

                <div class="hidden">
                    <InputText v-model="form.place_id" />
                    <InputText v-model="form.address_line" />
                    <InputText v-model="form.address_number" />
                    <InputText v-model="form.neighborhood" />
                    <InputText v-model="form.city" />
                    <InputText v-model="form.state" />
                    <InputText v-model="form.postal_code" />
                    <InputText v-model="form.country" />
                </div>

                <div v-if="form.city || form.state || form.latitude || form.longitude" class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="mb-2 font-semibold">Resumo da localização</p>
                    <div class="grid gap-2 md:grid-cols-2">
                        <p><strong>Cidade/UF:</strong> {{ form.city || '-' }} / {{ form.state || '-' }}</p>
                        <p><strong>CEP:</strong> {{ form.postal_code || '-' }}</p>
                        <p><strong>País:</strong> {{ form.country || '-' }}</p>
                        <p><strong>Lat/Lng:</strong> {{ form.latitude || '-' }} / {{ form.longitude || '-' }}</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <label for="venue-lat">Latitude</label>
                    <InputNumber id="venue-lat" v-model="form.latitude" :min-fraction-digits="4" :max-fraction-digits="7" fluid />
                </div>

                <div class="space-y-2">
                    <label for="venue-lng">Longitude</label>
                    <InputNumber id="venue-lng" v-model="form.longitude" :min-fraction-digits="4" :max-fraction-digits="7" fluid />
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
                <Button type="submit" :loading="form.processing" label="Atualizar local" icon="pi pi-save" />
            </div>
        </form>
    </div>
</template>
