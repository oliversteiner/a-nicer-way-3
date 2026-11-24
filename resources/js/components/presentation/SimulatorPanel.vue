<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { usePresentationStore } from '@/stores/presentation';
import type { Board } from '@/types/presentation';
import { useDraggable } from '@vueuse/core';
import { Minus, Plus, Presentation, X } from 'lucide-vue-next';
import { storeToRefs } from 'pinia';
import { computed, ref } from 'vue';

const props = defineProps<{ board: Board | undefined }>();

const store = usePresentationStore();
const { panels, simulatorSolo, simulatorHeight } = storeToRefs(store);

const panel = ref<HTMLElement | null>(null);
const handle = ref<HTMLElement | null>(null);
const { style } = useDraggable(panel, { handle, initialValue: { x: 24, y: 72 } });

const width = computed(() => simulatorHeight.value / 2);
const screenText = computed(() => {
    const state = props.board?.simulator_state;
    return typeof state?.screen === 'string' ? state.screen : null;
});
</script>

<template>
    <template v-if="panels.simulator">
        <div v-if="simulatorSolo" class="fixed inset-0 z-40 bg-background/80" data-test="simulator-backdrop" @click="simulatorSolo = false" />
        <div
            ref="panel"
            class="fixed z-50 flex flex-col overflow-hidden rounded-[2rem] border-4 bg-card shadow-xl"
            :style="[style, { height: `${simulatorHeight}px`, width: `${width}px` }]"
            data-test="simulator"
        >
            <div ref="handle" class="flex cursor-move select-none items-center justify-between gap-1 border-b bg-muted px-2 py-1">
                <div class="flex">
                    <Button variant="ghost" size="icon" class="size-6" aria-label="Kleiner" @click="store.simulatorSmaller()"
                        ><Minus class="size-3"
                    /></Button>
                    <Button variant="ghost" size="icon" class="size-6" aria-label="Grösser" @click="store.simulatorBigger()"
                        ><Plus class="size-3"
                    /></Button>
                </div>
                <div class="flex">
                    <Button
                        variant="ghost"
                        size="icon"
                        class="size-6"
                        aria-label="Solo"
                        :aria-pressed="simulatorSolo"
                        @click="simulatorSolo = !simulatorSolo"
                        ><Presentation class="size-3"
                    /></Button>
                    <Button variant="ghost" size="icon" class="size-6" aria-label="Schliessen" @click="store.closePanel('simulator')"
                        ><X class="size-3"
                    /></Button>
                </div>
            </div>
            <div
                class="flex flex-1 items-center justify-center bg-background p-3 text-center text-sm text-muted-foreground"
                data-test="simulator-screen"
            >
                <template v-if="board">
                    <div>
                        <p class="font-medium text-foreground">{{ board.title }}</p>
                        <p class="mt-1">{{ screenText ?? 'Kein Inhalt' }}</p>
                    </div>
                </template>
            </div>
            <div class="flex justify-center border-t py-2"><span class="size-5 rounded-full border-2" /></div>
        </div>
    </template>
</template>
