<script setup lang="ts">
import { usePresentationStore } from '@/stores/presentation';
import type { Board } from '@/types/presentation';
import { StickyNote } from 'lucide-vue-next';
import FloatingPanel from './FloatingPanel.vue';

defineProps<{ board: Board | undefined }>();

const store = usePresentationStore();
</script>

<template>
    <FloatingPanel title="Notizen" :icon="StickyNote" anchor="bottom-right" @close="store.closePanel('notes')">
        <div v-if="board" class="space-y-2 p-3 text-sm" data-test="notes">
            <h3 class="font-medium">{{ board.position }}. {{ board.title }}</h3>
            <p v-if="board.notes" class="whitespace-pre-line text-muted-foreground">{{ board.notes }}</p>
            <p v-else class="text-muted-foreground">Keine Notizen zu diesem Board.</p>
        </div>
    </FloatingPanel>
</template>
