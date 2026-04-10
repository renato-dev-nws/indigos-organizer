<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoFormSection from '@/Components/ui/BoFormSection.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';

const props = defineProps({ venue: Object, sizes: Array });
defineOptions({ layout: AppLayout });

const form = useForm({
    name: props.venue.name,
    email: props.venue.email,
    phone: props.venue.phone,
    contact_name: props.venue.contact_name,
    venue_size_id: props.venue.venue_size_id,
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
        <BoPageHeader title="Editar casa de show" subtitle="Atualize contatos, redes e informacoes comerciais">
            <template #actions>
                <Link :href="route('venues.show', venue.id)">
                    <Button label="Visualizar" icon="pi pi-eye" outlined severity="secondary" />
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
                    <label for="venue-size">Porte</label>
                    <Select id="venue-size" v-model="form.venue_size_id" :options="sizes" option-label="name" option-value="id" show-clear fluid />
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
                <Button type="submit" :loading="form.processing" label="Atualizar venue" icon="pi pi-save" />
            </div>
        </form>
    </div>
</template>
