<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { usePresentationStore } from '@/stores/presentation';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';

const store = usePresentationStore();
const { currentIndex, boardCount, isFirst, isLast } = storeToRefs(store);
</script>

<template>
    <nav class="flex items-center justify-center gap-2 border-t bg-card p-2" aria-label="Boards">
        <Button variant="ghost" size="icon" :disabled="isFirst" aria-label="Zurück" @click="store.goToPrevious()"><ChevronLeft /></Button>
        <button
            v-for="index in boardCount"
            :key="index"
            type="button"
            class="size-2.5 rounded-full border"
            :class="index - 1 === currentIndex ? 'bg-primary' : 'bg-muted'"
            :aria-label="`Board ${index}`"
            :aria-current="index - 1 === currentIndex ? 'true' : undefined"
            @click="store.goTo(index - 1)"
        />
        <Button variant="ghost" size="icon" :disabled="isLast" aria-label="Weiter" @click="store.goToNext()"><ChevronRight /></Button>
    </nav>
</template>
