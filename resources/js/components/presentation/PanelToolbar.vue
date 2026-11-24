<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { usePresentationStore, type PanelName } from '@/stores/presentation';
import { Cast, List, Smartphone, StickyNote, Terminal, Timer } from 'lucide-vue-next';
import type { Component } from 'vue';

const store = usePresentationStore();

const items: { name: PanelName; label: string; icon: Component }[] = [
    { name: 'boards', label: 'Boards', icon: List },
    { name: 'notes', label: 'Notizen', icon: StickyNote },
    { name: 'simulator', label: 'Simulator', icon: Smartphone },
    { name: 'console', label: 'Konsole', icon: Terminal },
    { name: 'timer', label: 'Timer', icon: Timer },
    { name: 'remote', label: 'Fernsteuerung', icon: Cast },
];
</script>

<template>
    <div class="flex items-center gap-1" role="toolbar" aria-label="Panels">
        <Button
            v-for="item in items"
            :key="item.name"
            variant="ghost"
            size="icon"
            :class="{ 'bg-accent': store.panels[item.name] }"
            :title="item.label"
            :aria-label="`${item.label} ein- oder ausblenden`"
            :aria-pressed="store.panels[item.name]"
            @click="store.togglePanel(item.name)"
        >
            <component :is="item.icon" />
        </Button>
    </div>
</template>
