<script setup lang="ts">
import { usePresentationStore } from '@/stores/presentation';
import type { Board } from '@/types/presentation';
import { List } from 'lucide-vue-next';
import FloatingPanel from './FloatingPanel.vue';

defineProps<{ boards: Board[] }>();

const store = usePresentationStore();
</script>

<template>
    <FloatingPanel title="Boards" :icon="List" anchor="top-right" @close="store.closePanel('boards')">
        <ol class="p-1" data-test="board-list">
            <li v-for="(board, index) in boards" :key="board.id">
                <button
                    type="button"
                    class="flex w-full items-baseline gap-2 rounded-md px-2 py-1.5 text-left text-sm hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    :class="{ 'bg-accent font-medium': index === store.currentIndex }"
                    :aria-current="index === store.currentIndex ? 'true' : undefined"
                    @click="store.goTo(index)"
                >
                    <span class="w-5 shrink-0 text-right text-muted-foreground">{{ board.position }}</span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate">{{ board.title }}</span>
                        <span v-if="board.description" class="block truncate text-xs text-muted-foreground">{{ board.description }}</span>
                    </span>
                </button>
            </li>
        </ol>
    </FloatingPanel>
</template>
