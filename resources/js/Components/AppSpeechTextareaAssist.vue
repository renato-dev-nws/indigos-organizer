<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    language: {
        type: String,
        default: 'pt-BR',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const isRecording = ref(false);
const errorMessage = ref('');
const recognitionRef = ref(null);

const isSupported = computed(() => {
    return typeof window !== 'undefined' && !!(window.SpeechRecognition || window.webkitSpeechRecognition);
});

const appendText = (text) => {
    const clean = `${text || ''}`.trim();
    if (!clean) {
        return;
    }

    const separator = props.modelValue?.trim() ? ' ' : '';
    emit('update:modelValue', `${props.modelValue || ''}${separator}${clean}`.trim());
};

const stopRecording = () => {
    recognitionRef.value?.stop();
    isRecording.value = false;
};

const startRecording = () => {
    if (!isSupported.value || props.disabled) {
        return;
    }

    errorMessage.value = '';

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    const recognition = new SpeechRecognition();
    recognition.lang = props.language;
    recognition.interimResults = true;
    recognition.continuous = true;

    recognition.onresult = (event) => {
        let finalTranscript = '';

        for (let i = event.resultIndex; i < event.results.length; i += 1) {
            const result = event.results[i];
            if (result.isFinal) {
                finalTranscript += result[0]?.transcript || '';
            }
        }

        appendText(finalTranscript);
    };

    recognition.onerror = (event) => {
        if (event?.error === 'not-allowed' || event?.error === 'service-not-allowed') {
            errorMessage.value = 'Permissão de microfone negada.';
        } else {
            errorMessage.value = 'Não foi possível transcrever o áudio agora.';
        }
        isRecording.value = false;
    };

    recognition.onend = () => {
        isRecording.value = false;
    };

    recognition.start();
    recognitionRef.value = recognition;
    isRecording.value = true;
};

const toggleRecording = () => {
    if (isRecording.value) {
        stopRecording();
        return;
    }

    startRecording();
};

onBeforeUnmount(() => {
    stopRecording();
});
</script>

<template>
    <div class="mb-2 flex items-center justify-end gap-2">
        <Button
            type="button"
            :icon="isRecording ? 'pi pi-stop-circle' : 'pi pi-microphone'"
            :label="isRecording ? 'Parar gravação' : 'Transcrever por voz'"
            :severity="isRecording ? 'danger' : 'secondary'"
            size="small"
            outlined
            :disabled="disabled || !isSupported"
            @click="toggleRecording"
        />
        <small v-if="isRecording" class="text-rose-600">Gravando...</small>
        <small v-else-if="!isSupported" class="text-slate-500">Navegador sem suporte à transcrição</small>
    </div>
    <Message v-if="errorMessage" severity="error" size="small" variant="simple">{{ errorMessage }}</Message>
</template>
