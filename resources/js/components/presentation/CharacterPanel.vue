<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { usePresentationStore } from '@/stores/presentation';
import type { Character, CharacterPose } from '@/types/presentation';
import { Coffee, Footprints, Hand, PersonStanding, User } from 'lucide-vue-next';
import type { Component } from 'vue';
import FloatingPanel from './FloatingPanel.vue';

defineProps<{ characters: Character[] }>();

const store = usePresentationStore();

const poses: { pose: CharacterPose; label: string; icon: Component }[] = [
    { pose: 'idle', label: 'Stehen', icon: PersonStanding },
    { pose: 'go', label: 'Gehen', icon: Footprints },
    { pose: 'pause', label: 'Pause', icon: Coffee },
    { pose: 'greet', label: 'Grüssen', icon: Hand },
];
</script>

<template>
    <FloatingPanel title="Figur" :icon="User" anchor="bottom-left" :width="280" @close="store.closePanel('character')">
        <div class="space-y-3 p-3 text-sm" data-test="character-panel">
            <p v-if="characters.length === 0" class="text-muted-foreground">Diese Journey hat keine Figuren.</p>

            <template v-else>
                <div class="flex flex-wrap gap-2" role="group" aria-label="Figur wählen">
                    <Button
                        v-for="character in characters"
                        :key="character.id"
                        size="sm"
                        :variant="store.characterId === character.id ? 'default' : 'secondary'"
                        :aria-pressed="store.characterId === character.id"
                        @click="store.selectCharacter(character.id)"
                    >
                        {{ character.name }}
                    </Button>
                </div>

                <div class="grid grid-cols-2 gap-2" role="group" aria-label="Aktion">
                    <Button
                        v-for="item in poses"
                        :key="item.pose"
                        size="sm"
                        :variant="store.characterPose === item.pose ? 'default' : 'secondary'"
                        :aria-pressed="store.characterPose === item.pose"
                        @click="store.setPose(item.pose)"
                    >
                        <component :is="item.icon" />{{ item.label }}
                    </Button>
                </div>
            </template>
        </div>
    </FloatingPanel>
</template>
