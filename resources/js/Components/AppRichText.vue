<script setup>
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import { onBeforeUnmount, watch } from 'vue';
import { Icon } from '@iconify/vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    minHeight: {
        type: Number,
        default: 220,
    },
});

const emit = defineEmits(['update:modelValue']);

const editor = useEditor({
    extensions: [StarterKit],
    content: props.modelValue,
    onUpdate: ({ editor: instance }) => {
        emit('update:modelValue', instance.getHTML());
    },
});

watch(
    () => props.modelValue,
    (value) => {
        if (!editor.value) return;

        const current = editor.value.getHTML();
        if (value !== current) {
            editor.value.commands.setContent(value || '', false);
        }
    },
);

onBeforeUnmount(() => {
    editor.value?.destroy();
});

const toolbarActions = [
    { icon: 'ph:text-b-bold', label: 'Negrito', action: () => editor.value?.chain().focus().toggleBold().run(), canRun: () => editor.value?.can().chain().focus().toggleBold().run() },
    { icon: 'ph:text-italic-bold', label: 'Itálico', action: () => editor.value?.chain().focus().toggleItalic().run(), canRun: () => editor.value?.can().chain().focus().toggleItalic().run() },
    { icon: 'ph:list-bullets-bold', label: 'Lista', action: () => editor.value?.chain().focus().toggleBulletList().run(), canRun: () => true },
    { icon: 'ph:list-numbers-bold', label: 'Lista numerada', action: () => editor.value?.chain().focus().toggleOrderedList().run(), canRun: () => true },
    { icon: 'ph:arrow-counter-clockwise-bold', label: 'Desfazer', action: () => editor.value?.chain().focus().undo().run(), canRun: () => true },
    { icon: 'ph:arrow-clockwise-bold', label: 'Refazer', action: () => editor.value?.chain().focus().redo().run(), canRun: () => true },
];
</script>

<template>
    <div class="bo-rich-editor-wrap overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700/80 dark:bg-slate-900">
        <div class="flex flex-wrap gap-0.5 border-b border-slate-200 bg-slate-50/80 p-1.5 dark:border-slate-700/80 dark:bg-slate-800/60">
            <button
                v-for="btn in toolbarActions"
                :key="btn.label"
                type="button"
                class="flex h-7 w-7 items-center justify-center rounded-md transition-colors"
                :class="btn.canRun()
                    ? 'text-slate-600 hover:bg-slate-200 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200'
                    : 'cursor-not-allowed text-slate-300 dark:text-slate-600'"
                :disabled="!btn.canRun()"
                :aria-label="btn.label"
                :title="btn.label"
                @click="btn.action()"
            >
                <Icon :icon="btn.icon" class="h-[14px] w-[14px]" />
            </button>
        </div>

        <EditorContent :editor="editor" class="bo-rich-editor p-3" :style="{ minHeight: `${minHeight}px` }" />
    </div>
</template>

<style scoped>
:deep(.bo-rich-editor .ProseMirror) {
    min-height: inherit;
    outline: none;
    font-size: 0.875rem;
    line-height: 1.6;
    color: inherit;
}

:deep(.bo-rich-editor .ProseMirror ul) {
    list-style-type: disc;
    padding-left: 1.4rem;
    margin: 0.25rem 0;
}

:deep(.bo-rich-editor .ProseMirror ol) {
    list-style-type: decimal;
    padding-left: 1.4rem;
    margin: 0.25rem 0;
}

:deep(.bo-rich-editor .ProseMirror strong) {
    font-weight: 600;
}

:deep(.bo-rich-editor .ProseMirror em) {
    font-style: italic;
}

:deep(.bo-rich-editor .ProseMirror p) {
    margin: 0;
}

:deep(.bo-rich-editor .ProseMirror > * + *) {
    margin-top: 0.4rem;
}

:deep(.bo-rich-editor .ProseMirror p.is-editor-empty:first-child::before) {
    content: attr(data-placeholder);
    float: left;
    color: #9ca3af;
    pointer-events: none;
    height: 0;
}
</style>
