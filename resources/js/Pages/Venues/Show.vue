<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import 'iconify-icon';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';

defineOptions({ layout: AppLayout });

const goBack = () => {
    if (typeof window !== 'undefined' && window.history.length > 1) {
        window.history.back();
    }
};

const props = defineProps({ venue: Object });

const statusLabels = {
    undefined: 'Indefinido',
    not_relevant: 'Não relevante',
    contacted: 'Contatado',
    vetoed: 'Vetado',
    negotiating: 'Em negociação',
    open_doors: 'Portas abertas',
};

const ratingStars = computed(() => {
    const normalized = Math.max(0, Math.min(5, Number(props.venue?.rating) || 0));
    return `${'★'.repeat(normalized)}${'☆'.repeat(5 - normalized)}`;
});

const performancesLabel = computed(() => {
    const value = Number(props.venue?.performances_count ?? 0);
    if (!Number.isFinite(value) || value <= 0) {
        return 'Nenhuma';
    }

    if (value === 1) {
        return '1 vez';
    }

    return `${value} vezes`;
});

const fullAddress = computed(() => [
    props.venue?.address_line,
    props.venue?.address_number,
    props.venue?.neighborhood,
    [props.venue?.city, props.venue?.state].filter(Boolean).join('/'),
    props.venue?.postal_code,
    props.venue?.country,
].filter(Boolean).join(', '));

const mapEmbedUrl = computed(() => {
    if (props.venue?.latitude && props.venue?.longitude) {
        return `https://maps.google.com/maps?q=${props.venue.latitude},${props.venue.longitude}&z=15&output=embed`;
    }

    if (fullAddress.value) {
        return `https://maps.google.com/maps?q=${encodeURIComponent(fullAddress.value)}&z=14&output=embed`;
    }

    return '';
});
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader :title="venue.name" subtitle="Ficha detalhada do local">
            <template #actions>
                <div>
                    <Button type="button" class="!hidden md:!inline-flex" icon="pi pi-arrow-left" label="Voltar" outlined severity="secondary" @click="goBack" />
                    <Button type="button" class="!inline-flex md:!hidden" icon="pi pi-arrow-left" rounded outlined severity="secondary" aria-label="Voltar" @click="goBack" />
                </div>
                <Link :href="route('venues.edit', venue.id)">
                    <Button type="button" class="!hidden md:!inline-flex" icon="pi pi-pencil" label="Editar" />
                    <Button type="button" class="!inline-flex md:!hidden" icon="pi pi-pencil" rounded aria-label="Editar" />
                </Link>
            </template>
        </BoPageHeader>

        <div class="grid gap-4 xl:grid-cols-3">
            <Card class="xl:col-span-2">
                <template #title>Resumo</template>
                <template #content>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2 text-sm">
                            <p><strong>Tipo:</strong> {{ venue.type?.name || '-' }}</p>
                            <p><strong>Categoria:</strong> {{ venue.category?.name || '-' }}</p>
                            <p><strong>Estilos:</strong></p>
                            <div class="mt-1 flex flex-wrap gap-1">
                                <Tag
                                    v-for="style in venue.styles || []"
                                    :key="style.id"
                                    severity="secondary"
                                    class="!px-1.5 !py-0.5"
                                >
                                    <template #default>
                                        <iconify-icon :icon="style.icon || 'mdi:palette-outline'" width="14" height="14" />
                                        <span class="ml-1">{{ style.name }}</span>
                                    </template>
                                </Tag>
                                <span v-if="!(venue.styles || []).length">-</span>
                            </div>
                        </div>
                        <div class="space-y-2 text-sm">
                            <p><strong>Status:</strong> <Tag :value="statusLabels[venue.status] || venue.status || '-'" /></p>
                            <p><strong>Vezes que se apresentou:</strong> {{ performancesLabel }}</p>
                            <p><strong>Avaliação:</strong> <span class="text-amber-500">{{ ratingStars }}</span></p>
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
                <template #title>Descrição e Observações</template>
                <template #content>
                    <p class="mb-3 text-sm">{{ venue.description || 'Sem descrição cadastrada.' }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ venue.notes || 'Sem observações cadastradas.' }}</p>

                    <hr class="my-4 border-slate-200 dark:border-slate-700" />

                    <h4 class="mb-2 text-sm font-semibold">Redes sociais</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a v-if="venue.instagram_url" :href="venue.instagram_url" target="_blank" rel="noopener" class="text-indigo-600 underline dark:text-indigo-400">Instagram</a><span v-else>-</span></li>
                        <li><a v-if="venue.facebook_url" :href="venue.facebook_url" target="_blank" rel="noopener" class="text-indigo-600 underline dark:text-indigo-400">Facebook</a><span v-else>-</span></li>
                        <li><a v-if="venue.youtube_url" :href="venue.youtube_url" target="_blank" rel="noopener" class="text-indigo-600 underline dark:text-indigo-400">YouTube</a><span v-else>-</span></li>
                        <li><a v-if="venue.website_url" :href="venue.website_url" target="_blank" rel="noopener" class="text-indigo-600 underline dark:text-indigo-400">Site</a><span v-else>-</span></li>
                    </ul>

                    <hr class="my-4 border-slate-200 dark:border-slate-700" />

                    <h4 class="mb-2 text-sm font-semibold">Contato</h4>
                    <div class="grid gap-2 text-sm md:grid-cols-2">
                        <p><strong>Responsável:</strong> {{ venue.contact_name || '-' }}</p>
                        <p><strong>E-mail:</strong> {{ venue.email || '-' }}</p>
                        <p><strong>Telefone:</strong> {{ venue.phone || '-' }}</p>
                        <p>
                            <strong>WhatsApp:</strong>
                            <a
                                v-if="venue.whatsapp"
                                :href="`https://wa.me/${String(venue.whatsapp).replace(/\D/g, '')}`"
                                target="_blank"
                                rel="noopener"
                                class="text-emerald-600 underline dark:text-emerald-400"
                            >
                                {{ venue.whatsapp }}
                            </a>
                            <span v-else>-</span>
                        </p>
                    </div>
                </template>
            </Card>

            <Card class="xl:col-span-1">
                <template #title>Localização</template>
                <template #content>
                    <div class="space-y-1 text-sm">
                        <p><strong>Endereço:</strong> {{ fullAddress || '-' }}</p>
                        <p><strong>Bairro:</strong> {{ venue.neighborhood || '-' }}</p>
                        <p><strong>Cidade/UF:</strong> {{ venue.city || '-' }} / {{ venue.state || '-' }}</p>
                        <p><strong>País:</strong> {{ venue.country || '-' }}</p>
                        <p><strong>CEP:</strong> {{ venue.postal_code || '-' }}</p>
                        <p><strong>Lat/Lng:</strong> {{ venue.latitude || '-' }} / {{ venue.longitude || '-' }}</p>
                    </div>

                    <div v-if="mapEmbedUrl" class="mt-3 overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700">
                        <iframe
                            :src="mapEmbedUrl"
                            class="h-52 w-full"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                        />
                    </div>
                </template>
            </Card>
        </div>
    </div>
</template>
