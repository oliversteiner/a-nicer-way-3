<script setup lang="ts">
import { usePresentationStore } from '@/stores/presentation';
import type { AssetLayerName, Board } from '@/types/presentation';
import { useElementSize, useEventListener, useScroll } from '@vueuse/core';
import { computed, ref, watch } from 'vue';

const props = defineProps<{ boards: Board[] }>();

const store = usePresentationStore();
const scroller = ref<HTMLElement | null>(null);
const { width: viewport } = useElementSize(scroller);
const { x: scrollX } = useScroll(scroller, { behavior: 'smooth' });

/** Parallax speed per layer: back moves slowest, front fastest. */
const layers: { name: AssetLayerName; speed: number }[] = [
    { name: 'back', speed: 0.5 },
    { name: 'middle', speed: 1 },
    { name: 'front', speed: 1.5 },
];

const count = computed(() => props.boards.length);
const trackWidth = computed(() => viewport.value * count.value);

function layerWidth(speed: number) {
    return viewport.value * ((count.value - 1) * speed + 1);
}

function slotLeft(index: number, speed: number) {
    return index * viewport.value * speed;
}

function assetsFor(board: Board, layer: AssetLayerName) {
    return board.assets.filter((asset) => asset.layer === layer);
}

watch(
    () => [viewport.value, scrollX.value],
    () => {
        if (viewport.value > 0) store.setCurrent(Math.round(scrollX.value / viewport.value));
    },
);

watch(
    () => store.scrollTarget,
    (target) => {
        if (target === null) return;
        scroller.value?.scrollTo({ left: target * viewport.value, behavior: 'smooth' });
        store.scrollTarget = null;
    },
);

useEventListener(window, 'keydown', (event: KeyboardEvent) => {
    if (event.key === 'ArrowRight') store.goToNext();
    if (event.key === 'ArrowLeft') store.goToPrevious();
});
</script>

<template>
    <div
        ref="scroller"
        class="relative flex-1 snap-x snap-mandatory overflow-x-auto overflow-y-hidden [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
        data-test="scroller"
    >
        <div class="relative h-full" :style="{ width: `${trackWidth}px` }">
            <div class="sticky left-0 h-full overflow-hidden" :style="{ width: `${viewport}px` }">
                <div
                    v-for="layer in layers"
                    :key="layer.name"
                    class="absolute inset-y-0 left-0"
                    :class="{ 'pointer-events-none': layer.name !== 'middle' }"
                    :style="{ width: `${layerWidth(layer.speed)}px`, transform: `translateX(${-scrollX * layer.speed}px)` }"
                    :data-layer="layer.name"
                >
                    <div
                        v-for="(board, index) in boards"
                        :key="board.id"
                        class="absolute inset-y-0 flex items-center justify-center"
                        :style="{ left: `${slotLeft(index, layer.speed)}px`, width: `${viewport}px` }"
                    >
                        <img
                            v-for="asset in assetsFor(board, layer.name)"
                            :key="asset.url"
                            :src="asset.url"
                            alt=""
                            class="max-h-full max-w-full object-contain"
                        />
                        <div
                            v-if="layer.name === 'middle'"
                            class="max-w-md rounded-lg border bg-card p-6 text-card-foreground shadow-sm"
                            data-test="board"
                        >
                            <h2 class="text-lg font-medium">{{ board.position }}. {{ board.title }}</h2>
                            <p v-if="board.notes" class="mt-2 text-sm text-muted-foreground">{{ board.notes }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Scroll snap targets: one per board, aligned to the track. -->
            <div
                v-for="(board, index) in boards"
                :key="`snap-${board.id}`"
                class="absolute inset-y-0 snap-start"
                :style="{ left: `${index * viewport}px`, width: `${viewport}px` }"
            />
        </div>
    </div>
</template>
