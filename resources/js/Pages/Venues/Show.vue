<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';

defineOptions({ layout: AppLayout });

const goBack = () => {
    if (typeof window !== 'undefined' && window.history.length > 1) {
        window.history.back();
    }
};
defineProps({ venue: Object });
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader :title="venue.name" subtitle="Ficha detalhada do local">
            <template #actions>
                <div>
                    <Button class="!hidden md:!inline-flex" icon="pi pi-arrow-left" label="Voltar" outlined severity="secondary" @click="goBack" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-arrow-left" rounded outlined severity="secondary" aria-label="Voltar" @click="goBack" />
                </div>
                <Link :href="route('venues.edit', venue.id)">
                    <Button class="!hidden md:!inline-flex" icon="pi pi-pencil" label="Editar" />
                    <Button class="!inline-flex md:!hidden" icon="pi pi-pencil" rounded aria-label="Editar" />
                </Link>
            </template>
        </BoPageHeader>

        <div class="grid gap-4 xl:grid-cols-3">
            <Card class="xl:col-span-2">
                <template #title>Resumo</template>
                <template #content>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2 text-sm">
                            <p><strong>Responsável:</strong> {{ venue.contact_name || '-' }}</p>
                            <p><strong>E-mail:</strong> {{ venue.email || '-' }}</p>
                            <p><strong>Telefone:</strong> {{ venue.phone || '-' }}</p>
                            <p><strong>Status:</strong> {{ venue.status || '-' }}</p>
                            <p><strong>Avaliação:</strong> {{ venue.rating || '-' }}</p>
                        </div>
                        <div class="space-y-2 text-sm">
                            <p><strong>Tipo:</strong> {{ venue.type?.name || '-' }}</p>
                            <p><strong>Categoria:</strong> {{ venue.category?.name || '-' }}</p>
                            <p><strong>Estilo:</strong> {{ venue.style?.name || '-' }}</p>
                            <p><strong>Tamanho:</strong> {{ venue.size?.name || '-' }}</p>
                            <p><strong>Vezes que já tocou:</strong> {{ venue.performances_count ?? 0 }}</p>
                        </div>
                    </div>
                </template>
            </Card>

            <Card class="xl:col-span-1">
                <template #title>Equipamentos</template>
                <template #content>
                    <div v-if="venue.equipment_tags?.length" class="flex flex-wrap gap-1">
                        <Tag v-for="equipment in venue.equipment_tags" :key="equipment" :value="equipment" severity="secondary" />
                    </div>
                    <p v-else class="text-sm text-slate-500">Nenhum equipamento informado.</p>
                </template>
            </Card>

            <Card class="xl:col-span-2">
                <template #title>Observações</template>
                <template #content>
                    <p class="mb-3 text-sm">{{ venue.description || 'Sem descrição cadastrada.' }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ venue.notes || 'Sem observações cadastradas.' }}</p>
                </template>
            </Card>

            <Card class="xl:col-span-1">
                <template #title>Redes e localização</template>
                <template #content>
                    <ul class="mb-4 space-y-2 text-sm">
                        <li><a v-if="venue.instagram_url" :href="venue.instagram_url" target="_blank" rel="noopener" class="text-indigo-600 underline dark:text-indigo-400">Instagram</a><span v-else>-</span></li>
                        <li><a v-if="venue.facebook_url" :href="venue.facebook_url" target="_blank" rel="noopener" class="text-indigo-600 underline dark:text-indigo-400">Facebook</a><span v-else>-</span></li>
                        <li><a v-if="venue.youtube_url" :href="venue.youtube_url" target="_blank" rel="noopener" class="text-indigo-600 underline dark:text-indigo-400">YouTube</a><span v-else>-</span></li>
                        <li>
                            <a v-if="venue.whatsapp" :href="`https://wa.me/${String(venue.whatsapp).replace(/\D/g, '')}`" target="_blank" rel="noopener" class="text-emerald-600 underline dark:text-emerald-400">WhatsApp</a>
                            <span v-else>-</span>
                        </li>
                        <li><a v-if="venue.website_url" :href="venue.website_url" target="_blank" rel="noopener" class="text-indigo-600 underline dark:text-indigo-400">Site</a><span v-else>-</span></li>
                    </ul>

                    <div class="space-y-1 text-sm">
                        <p><strong>Endereço:</strong> {{ venue.address_line || '-' }}, {{ venue.address_number || '-' }}</p>
                        <p><strong>Bairro:</strong> {{ venue.neighborhood || '-' }}</p>
                        <p><strong>Cidade/UF:</strong> {{ venue.city || '-' }} / {{ venue.state || '-' }}</p>
                        <p><strong>País:</strong> {{ venue.country || '-' }}</p>
                        <p><strong>CEP:</strong> {{ venue.postal_code || '-' }}</p>
                        <p><strong>Lat/Lng:</strong> {{ venue.latitude || '-' }} / {{ venue.longitude || '-' }}</p>
                    </div>
                </template>
            </Card>
        </div>
    </div>
</template>
