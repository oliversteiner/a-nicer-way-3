<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { usePresentationStore } from '@/stores/presentation';
import type { Board } from '@/types/presentation';
import { Clock, MapPin, Moon, Smartphone, Sun } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{ title: string; board: Board | undefined }>();

const store = usePresentationStore();

const time = computed(() => props.board?.time?.slice(0, 5) ?? null);
</script>

<template>
    <header class="flex items-center gap-4 border-b bg-card px-4 py-2 text-sm text-card-foreground">
        <h1 class="font-semibold">{{ title }}</h1>
        <div v-if="board" class="ml-auto flex items-center gap-4 text-muted-foreground" data-test="status">
            <span>Tag {{ board.day }}</span>
            <span v-if="time" class="flex items-center gap-1"><Clock class="size-4" />{{ time }}</span>
            <span v-if="board.location" class="flex items-center gap-1"><MapPin class="size-4" />{{ board.location }}</span>
            <span v-if="board.mood">{{ board.mood }}</span>
            <component :is="board.is_night ? Moon : Sun" class="size-4" :aria-label="board.is_night ? 'Nacht' : 'Tag'" />
        </div>
        <Button variant="ghost" size="icon" aria-label="Simulator ein- oder ausblenden" @click="store.toggleSimulator()"><Smartphone /></Button>
    </header>
</template>
