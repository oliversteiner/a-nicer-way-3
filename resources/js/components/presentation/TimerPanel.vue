<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { usePresentationStore } from '@/stores/presentation';
import { Pause, Play, RotateCcw, Timer } from 'lucide-vue-next';
import { ref } from 'vue';
import FloatingPanel from './FloatingPanel.vue';

const store = usePresentationStore();

const minutes = ref(Math.round(store.timerTotal / 60));

const applyMinutes = () => {
    const value = Number(minutes.value);
    store.timerSetDuration((Number.isFinite(value) ? value : 0) * 60);
    minutes.value = Math.round(store.timerTotal / 60);
};
</script>

<template>
    <FloatingPanel title="Timer" :icon="Timer" anchor="top-center" :width="260" @close="store.closePanel('timer')">
        <div class="space-y-4 p-4 text-center">
            <p
                class="font-mono text-5xl font-semibold tabular-nums"
                :class="{ 'text-yellow-600': store.timerNearEnd, 'text-destructive': store.timerTimeOut }"
                role="timer"
                data-test="timer-display"
            >
                {{ store.timerDisplay }}
            </p>
            <p v-if="store.timerTimeOut" class="text-sm text-destructive" role="status">Die Zeit ist abgelaufen.</p>

            <div class="flex items-center justify-center gap-2">
                <Button :aria-label="store.timerRunning ? 'Pause' : 'Start'" @click="store.timerToggle()">
                    <component :is="store.timerRunning ? Pause : Play" />{{ store.timerRunning ? 'Pause' : 'Start' }}
                </Button>
                <Button variant="secondary" size="icon" aria-label="Zurücksetzen" @click="store.timerReset()"><RotateCcw /></Button>
            </div>

            <div class="flex items-center justify-center gap-2 text-sm">
                <Label for="timer-minutes">Minuten</Label>
                <Input
                    id="timer-minutes"
                    v-model="minutes"
                    type="number"
                    min="0"
                    max="999"
                    class="w-20"
                    :disabled="store.timerRunning"
                    @change="applyMinutes"
                />
            </div>
        </div>
    </FloatingPanel>
</template>
