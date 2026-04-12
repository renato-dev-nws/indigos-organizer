<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';

defineOptions({ layout: AppLayout });
defineProps({ venue: Object });
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader :title="venue.name" subtitle="Ficha detalhada do local">
            <template #actions>
                <Link :href="route('venues.index')">
                    <Button class="hidden md:inline-flex" icon="pi pi-arrow-left" label="Voltar" outlined severity="secondary" />
                    <Button class="inline-flex md:hidden" icon="pi pi-arrow-left" rounded outlined severity="secondary" aria-label="Voltar" />
                </Link>
                <Link :href="route('venues.edit', venue.id)">
                    <Button class="hidden md:inline-flex" icon="pi pi-pencil" label="Editar" />
                    <Button class="inline-flex md:hidden" icon="pi pi-pencil" rounded aria-label="Editar" />
                </Link>
            </template>
        </BoPageHeader>

        <div class="grid gap-4 xl:grid-cols-3">
            <Card class="xl:col-span-1">
                <template #title>Contato</template>
                <template #content>
                    <div class="space-y-2 text-sm">
                        <p><strong>Responsável:</strong> {{ venue.contact_name || '-' }}</p>
                        <p><strong>E-mail:</strong> {{ venue.email || '-' }}</p>
                        <p><strong>Telefone:</strong> {{ venue.phone || '-' }}</p>
                        <p><strong>Tipo:</strong> {{ venue.type?.name || '-' }}</p>
                        <p><strong>Categoria:</strong> {{ venue.category?.name || '-' }}</p>
                        <p><strong>Estilo:</strong> {{ venue.style?.name || '-' }}</p>
                        <p><strong>Status:</strong> {{ venue.status || '-' }}</p>
                    </div>
                </template>
            </Card>

            <Card class="xl:col-span-1">
                <template #title>Redes e links</template>
                <template #content>
                    <ul class="space-y-2 text-sm">
                        <li><a v-if="venue.instagram_url" :href="venue.instagram_url" target="_blank" rel="noopener" class="text-indigo-600 underline dark:text-indigo-400">Instagram</a><span v-else>-</span></li>
                        <li><a v-if="venue.facebook_url" :href="venue.facebook_url" target="_blank" rel="noopener" class="text-indigo-600 underline dark:text-indigo-400">Facebook</a><span v-else>-</span></li>
                        <li><a v-if="venue.youtube_url" :href="venue.youtube_url" target="_blank" rel="noopener" class="text-indigo-600 underline dark:text-indigo-400">YouTube</a><span v-else>-</span></li>
                        <li><a v-if="venue.website_url" :href="venue.website_url" target="_blank" rel="noopener" class="text-indigo-600 underline dark:text-indigo-400">Site</a><span v-else>-</span></li>
                    </ul>
                </template>
            </Card>

            <Card class="xl:col-span-1">
                <template #title>Observações e localização</template>
                <template #content>
                    <p class="mb-3 text-sm">{{ venue.description || 'Sem descrição cadastrada.' }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ venue.notes || 'Sem observações cadastradas.' }}</p>
                    <div class="mt-3 text-sm">
                        <p><strong>Endereço:</strong> {{ venue.address_line || '-' }}, {{ venue.address_number || '-' }}</p>
                        <p><strong>Bairro:</strong> {{ venue.neighborhood || '-' }}</p>
                        <p><strong>Cidade/UF:</strong> {{ venue.city || '-' }} / {{ venue.state || '-' }}</p>
                        <p><strong>País:</strong> {{ venue.country || '-' }}</p>
                        <p><strong>CEP:</strong> {{ venue.postal_code || '-' }}</p>
                    </div>
                </template>
            </Card>
        </div>
    </div>
</template>
