<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BoPageHeader from '@/Components/ui/BoPageHeader.vue';
import BoStatusTag from '@/Components/ui/BoStatusTag.vue';

defineOptions({ layout: AppLayout });
const props = defineProps({ idea: Object, userVote: Object });
const page = usePage();

const canVote = computed(() => {
    const currentUserId = page.props.auth?.user?.id;
    if (props.idea.status !== 'on_board') return false;
    if (!props.idea.voter_users?.length) return true;
    return props.idea.voter_users.some((user) => user.id === currentUserId);
});

const isCreator = computed(() => page.props.auth?.user?.id === props.idea.user_id);

const vote = (value) => {
    router.post(route('ideas.vote', props.idea.id), { vote: value }, { preserveScroll: true });
};
</script>

<template>
    <div class="space-y-6">
        <BoPageHeader :title="idea.title" subtitle="Detalhes completos da ideia">
            <template #actions>
                <Link :href="route('ideas.index')">
                    <Button class="hidden md:inline-flex" icon="pi pi-arrow-left" label="Voltar" outlined severity="secondary" />
                    <Button class="inline-flex md:hidden" icon="pi pi-arrow-left" rounded outlined severity="secondary" aria-label="Voltar" />
                </Link>
                <Link :href="route('ideas.edit', idea.id)">
                    <Button class="hidden md:inline-flex" icon="pi pi-pencil" label="Editar" />
                    <Button class="inline-flex md:hidden" icon="pi pi-pencil" rounded aria-label="Editar" />
                </Link>
            </template>
        </BoPageHeader>

        <Card>
            <template #content>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-sm text-slate-500">Status</p>
                        <BoStatusTag :value="idea.status" />
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Tipo</p>
                        <p class="font-semibold">{{ idea.type?.name || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Categoria</p>
                        <p class="font-semibold">{{ idea.category?.name || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Descrição</p>
                        <p>{{ idea.description || '-' }}</p>
                    </div>
                </div>
            </template>
        </Card>

        <Card v-if="canVote">
            <template #title>Votação</template>
            <template #content>
                <div class="flex flex-wrap gap-2">
                    <Button label="Na mesa" @click="vote('on_table')" />
                    <Button label="Na gaveta" severity="secondary" @click="vote('in_drawer')" />
                    <Button label="No lixo" severity="danger" @click="vote('trash')" />
                </div>
                <p v-if="userVote" class="mt-3 text-sm text-slate-500">Seu voto atual: {{ userVote.vote }}</p>
            </template>
        </Card>

        <Card>
            <template #title>Referências</template>
            <template #content>
                <DataTable :value="idea.references" data-key="id" striped-rows>
                    <Column field="title" header="Título" />
                    <Column field="description" header="Descrição" />
                    <Column header="URL">
                        <template #body="{ data }">
                            <a :href="data.url" target="_blank" rel="noopener" class="text-indigo-600 underline dark:text-indigo-400">{{ data.url }}</a>
                        </template>
                    </Column>
                </DataTable>
            </template>
        </Card>

        <Card v-if="isCreator">
            <template #title>Votos recebidos</template>
            <template #content>
                <DataTable :value="idea.votes" data-key="id" striped-rows>
                    <Column header="Usuário">
                        <template #body="{ data }">{{ data.user?.name || '-' }}</template>
                    </Column>
                    <Column field="vote" header="Voto" />
                </DataTable>
            </template>
        </Card>
    </div>
</template>
