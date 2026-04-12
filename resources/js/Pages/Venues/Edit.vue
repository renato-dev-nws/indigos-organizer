<script setup>
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
    equipment_tags: props.venue.equipment_tags,
    rating: props.venue.rating,
    instagram_url: props.venue.instagram_url,
    facebook_url: props.venue.facebook_url,
    youtube_url: props.venue.youtube_url,
    website_url: props.venue.website_url,
    notes: props.venue.notes,
    description: props.venue.description,
});

const submit = () => form.put(route('venues.update', props.venue.id));
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader title="Editar local" subtitle="Atualize contatos, classificação e localização">
            <template #actions>
                <Link :href="route('venues.show', venue.id)">
                    <Button class="hidden md:inline-flex" label="Visualizar" icon="pi pi-eye" outlined severity="secondary" />
                    <Button class="inline-flex md:hidden" icon="pi pi-eye" rounded outlined severity="secondary" aria-label="Visualizar" />
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
                    <label for="venue-site">Website</label>
                    <InputText id="venue-site" v-model="form.website_url" fluid />
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
                    <label for="venue-equipment">Equipamentos (csv)</label>
                    <InputText id="venue-equipment" v-model="form.equipment_tags" fluid />
                </div>

                <div class="space-y-2 md:col-span-2">
                    <label for="venue-address">Endereço</label>
                    <InputText id="venue-address" v-model="form.address_line" placeholder="Rua / avenida" fluid />
                </div>

                <div class="space-y-2">
                    <label for="venue-address-number">Número</label>
                    <InputText id="venue-address-number" v-model="form.address_number" fluid />
                </div>

                <div class="space-y-2">
                    <label for="venue-neighborhood">Bairro</label>
                    <InputText id="venue-neighborhood" v-model="form.neighborhood" fluid />
                </div>

                <div class="space-y-2">
                    <label for="venue-city">Cidade</label>
                    <InputText id="venue-city" v-model="form.city" fluid />
                </div>

                <div class="space-y-2">
                    <label for="venue-state">Estado</label>
                    <InputText id="venue-state" v-model="form.state" fluid />
                </div>

                <div class="space-y-2">
                    <label for="venue-postal-code">CEP</label>
                    <InputText id="venue-postal-code" v-model="form.postal_code" fluid />
                </div>

                <div class="space-y-2">
                    <label for="venue-country">País</label>
                    <InputText id="venue-country" v-model="form.country" fluid />
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
