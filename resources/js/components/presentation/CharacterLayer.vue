<script setup lang="ts">
import { usePresentationStore } from '@/stores/presentation';
import type { Character, CharacterPose } from '@/types/presentation';
import { computed } from 'vue';

const props = defineProps<{ characters: Character[] }>();

const store = usePresentationStore();

const poseLabel: Record<CharacterPose, string> = { idle: 'steht', go: 'geht', pause: 'macht Pause', greet: 'grüsst' };

const character = computed(() => props.characters.find((item) => item.id === store.characterId) ?? null);

// A pose without an image shows the standing image instead.
const image = computed(() => character.value?.images[store.characterPose] ?? character.value?.images.idle ?? null);

const description = computed(() => (character.value ? `${character.value.name} ${poseLabel[store.characterPose]}` : ''));

// Motion only for people who have not asked for less of it.
const motion = computed(
    () => ({ go: 'motion-safe:animate-bounce', greet: 'motion-safe:animate-pulse' })[store.characterPose as 'go' | 'greet'] ?? '',
);
</script>

<template>
    <div v-if="character" class="pointer-events-none absolute inset-x-0 bottom-0 flex h-2/5 items-end justify-center pb-4" data-test="character">
        <img v-if="image" :src="image" :alt="description" class="max-h-full max-w-full object-contain" :class="motion" />
        <div
            v-else
            class="rounded-lg bg-primary px-6 py-4 text-center text-primary-foreground shadow"
            :class="motion"
            role="img"
            :aria-label="description"
            data-test="character-placeholder"
        >
            <p class="font-semibold">{{ character.name }}</p>
            <p class="text-sm opacity-80">{{ poseLabel[store.characterPose] }}</p>
        </div>
    </div>
</template>
