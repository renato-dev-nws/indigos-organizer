<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    composePhoneWithCountryCode,
    formatBrazilPhoneInput,
    normalizeCountryCodeInput,
    splitPhoneByCountryCode,
} from '@/Utils/phone';

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
const phoneCountryCode = ref('55');
const whatsappCountryCode = ref('55');

const closeModal = () => emit('update:visible', false);

const hydrateForm = () => {
    hydrating.value = true;

    if (!props.contact) {
        phoneCountryCode.value = '55';
        whatsappCountryCode.value = '55';

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

    const phoneParts = splitPhoneByCountryCode(props.contact.phone);
    const whatsappParts = splitPhoneByCountryCode(props.contact.whatsapp);
    phoneCountryCode.value = phoneParts.countryCode;
    whatsappCountryCode.value = whatsappParts.countryCode;

    form.defaults({
        name: props.contact.name ?? '',
        email: props.contact.email ?? '',
        phone: formatBrazilPhoneInput(phoneParts.localDigits),
        whatsapp: formatBrazilPhoneInput(whatsappParts.localDigits),
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
    form.transform((data) => ({
        ...data,
        phone: composePhoneWithCountryCode(phoneCountryCode.value, data.phone),
        whatsapp: composePhoneWithCountryCode(whatsappCountryCode.value, data.whatsapp),
    }));

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
                    <InputGroup>
                        <InputGroupAddon>+</InputGroupAddon>
                        <InputText
                            :model-value="phoneCountryCode"
                            style="width: 54px; min-width: 54px; max-width: 54px"
                            inputmode="numeric"
                            @update:model-value="updatePhoneCountryCode"
                        />
                        <InputText :model-value="form.phone" :invalid="!!form.errors.phone" placeholder="(11) 3456-7890" fluid @update:model-value="updatePhone" />
                    </InputGroup>
                    <Message v-if="form.errors.phone" severity="error" size="small" variant="simple">{{ form.errors.phone }}</Message>
                </div>

                <div class="space-y-2">
                    <label>WhatsApp</label>
                    <InputGroup>
                        <InputGroupAddon>+</InputGroupAddon>
                        <InputText
                            :model-value="whatsappCountryCode"
                            style="width: 54px; min-width: 54px; max-width: 54px"
                            inputmode="numeric"
                            @update:model-value="updateWhatsappCountryCode"
                        />
                        <InputText :model-value="form.whatsapp" :invalid="!!form.errors.whatsapp" placeholder="(11) 98765-4321" fluid @update:model-value="updateWhatsapp" />
                    </InputGroup>
                    <Message v-if="form.errors.whatsapp" severity="error" size="small" variant="simple">{{ form.errors.whatsapp }}</Message>
                </div>

                <div class="space-y-2">
                    <label>Local relacionado</label>
                    <Select v-model="form.venue_id" :options="venues" option-label="name" option-value="id" show-clear filter placeholder="Sem local" :invalid="!!form.errors.venue_id" fluid />
                    <Message v-if="form.errors.venue_id" severity="error" size="small" variant="simple">{{ form.errors.venue_id }}</Message>
                </div>

                <div class="space-y-2 md:col-span-2">
                    <label>Descrição rápida</label>
                    <InputText v-model="form.description" fluid />
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <Button type="button" label="Cancelar" outlined severity="secondary" @click="closeModal" />
                <Button type="submit" :loading="form.processing" label="Salvar" icon="pi pi-save" />
            </div>
        </form>
    </Dialog>
</template>
