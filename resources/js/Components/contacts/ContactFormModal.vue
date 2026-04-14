<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    visible: { type: Boolean, default: false },
    contact: { type: Object, default: null },
    venues: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:visible', 'saved']);

const form = useForm({
    name: '',
    email: '',
    phone: '',
    whatsapp: '',
    description: '',
    venue_id: null,
});

const hydrating = ref(false);

const closeModal = () => emit('update:visible', false);

const hydrateForm = () => {
    hydrating.value = true;

    if (!props.contact) {
        form.defaults({
            name: '',
            email: '',
            phone: '',
            whatsapp: '',
            description: '',
            venue_id: null,
        });
        form.reset();
        hydrating.value = false;
        return;
    }

    form.defaults({
        name: props.contact.name ?? '',
        email: props.contact.email ?? '',
        phone: props.contact.phone ?? '',
        whatsapp: props.contact.whatsapp ?? '',
        description: props.contact.description ?? '',
        venue_id: props.contact.venue_id ?? null,
    });
    form.reset();
    hydrating.value = false;
};

watch(() => props.visible, (isVisible) => {
    if (isVisible) {
        hydrateForm();
    } else if (!hydrating.value) {
        form.clearErrors();
    }
});

const submit = () => {
    if (props.contact?.id) {
        form.put(route('contacts.update', props.contact.id), {
            preserveScroll: true,
            onSuccess: () => {
                emit('saved');
                closeModal();
            },
        });

        return;
    }

    form.post(route('contacts.store'), {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
            closeModal();
        },
    });
};
</script>

<template>
    <Dialog :visible="visible" modal :header="contact?.id ? 'Editar contato' : 'Novo contato'" :style="{ width: '44rem', maxWidth: '96vw' }" @update:visible="emit('update:visible', $event)">
        <form class="space-y-4" @submit.prevent="submit">
            <Message v-if="form.hasErrors" severity="error" size="small" variant="simple">
                {{ Object.values(form.errors)[0] }}
            </Message>

            <div class="grid gap-3 md:grid-cols-2">
                <div class="space-y-2 md:col-span-2">
                    <label>Nome</label>
                    <InputText v-model="form.name" :invalid="!!form.errors.name" fluid />
                    <Message v-if="form.errors.name" severity="error" size="small" variant="simple">{{ form.errors.name }}</Message>
                </div>

                <div class="space-y-2">
                    <label>Email</label>
                    <InputText v-model="form.email" :invalid="!!form.errors.email" fluid />
                    <Message v-if="form.errors.email" severity="error" size="small" variant="simple">{{ form.errors.email }}</Message>
                </div>

                <div class="space-y-2">
                    <label>Telefone</label>
                    <InputText v-model="form.phone" :invalid="!!form.errors.phone" fluid />
                    <Message v-if="form.errors.phone" severity="error" size="small" variant="simple">{{ form.errors.phone }}</Message>
                </div>

                <div class="space-y-2">
                    <label>WhatsApp</label>
                    <InputText v-model="form.whatsapp" :invalid="!!form.errors.whatsapp" fluid />
                    <Message v-if="form.errors.whatsapp" severity="error" size="small" variant="simple">{{ form.errors.whatsapp }}</Message>
                </div>

                <div class="space-y-2">
                    <label>Local relacionado</label>
                    <Select v-model="form.venue_id" :options="venues" option-label="name" option-value="id" show-clear placeholder="Sem local" :invalid="!!form.errors.venue_id" fluid />
                    <Message v-if="form.errors.venue_id" severity="error" size="small" variant="simple">{{ form.errors.venue_id }}</Message>
                </div>

                <div class="space-y-2 md:col-span-2">
                    <label>Descrição</label>
                    <Textarea v-model="form.description" rows="4" fluid />
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <Button type="button" label="Cancelar" outlined severity="secondary" @click="closeModal" />
                <Button type="submit" :loading="form.processing" label="Salvar" icon="pi pi-save" />
            </div>
        </form>
    </Dialog>
</template>
