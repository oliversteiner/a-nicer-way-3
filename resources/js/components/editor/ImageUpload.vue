<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Upload } from 'lucide-vue-next';

withDefaults(defineProps<{ disabled?: boolean; label?: string }>(), { disabled: false, label: 'Upload' });

const emit = defineEmits<{ (e: 'select', file: File): void }>();

function onChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    // Clear the field so the same file can be chosen again.
    input.value = '';

    if (file) emit('select', file);
}
</script>

<template>
    <Button type="button" variant="secondary" size="sm" as-child :disabled="disabled">
        <label class="cursor-pointer">
            <Upload />{{ label }}
            <input type="file" class="sr-only" accept="image/png,image/jpeg,image/webp,image/svg+xml" :disabled="disabled" @change="onChange" />
        </label>
    </Button>
</template>
