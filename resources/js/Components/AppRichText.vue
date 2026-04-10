<script setup>
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import { onBeforeUnmount, watch } from 'vue';

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
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-950">
        <div class="flex flex-wrap gap-1 border-b border-slate-200 p-2 dark:border-slate-700">
            <Button type="button" text size="small" icon="pi pi-bold" aria-label="Negrito" @click="editor?.chain().focus().toggleBold().run()" :disabled="!editor?.can().chain().focus().toggleBold().run()" />
            <Button type="button" text size="small" icon="pi pi-italic" aria-label="Italico" @click="editor?.chain().focus().toggleItalic().run()" :disabled="!editor?.can().chain().focus().toggleItalic().run()" />
            <Button type="button" text size="small" icon="pi pi-list" aria-label="Lista" @click="editor?.chain().focus().toggleBulletList().run()" />
            <Button type="button" text size="small" icon="pi pi-list-check" aria-label="Lista numerada" @click="editor?.chain().focus().toggleOrderedList().run()" />
            <Button type="button" text size="small" icon="pi pi-undo" aria-label="Desfazer" @click="editor?.chain().focus().undo().run()" />
            <Button type="button" text size="small" icon="pi pi-refresh" aria-label="Refazer" @click="editor?.chain().focus().redo().run()" />
        </div>

        <EditorContent :editor="editor" class="bo-rich-editor p-3" :style="{ minHeight: `${minHeight}px` }" />
    </div>
</template>

<style scoped>
:deep(.bo-rich-editor .ProseMirror) {
    min-height: inherit;
    outline: none;
}
</style>
