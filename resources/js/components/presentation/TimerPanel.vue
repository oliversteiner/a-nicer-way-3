<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useCountdown } from '@/composables/useCountdown';
import { useConsoleStore } from '@/stores/console';
import { usePresentationStore } from '@/stores/presentation';
import { Pause, Play, RotateCcw, Timer } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import FloatingPanel from './FloatingPanel.vue';

const store = usePresentationStore();
const log = useConsoleStore();

const countdown = useCountdown({
    seconds: 300,
    warningSeconds: 60,
    onFinish: () => log.log('timer', 'time is up', null, 'warning'),
});

const minutes = ref(Math.round(countdown.total.value / 60));

const applyMinutes = () => {
    const value = Number(minutes.value);
    countdown.setDuration((Number.isFinite(value) ? value : 0) * 60);
    minutes.value = Math.round(countdown.total.value / 60);
};

watch(countdown.running, (running, wasRunning) => {
    if (running) log.log('timer', 'started', countdown.display.value);
    else if (wasRunning && !countdown.timeOut.value) log.log('timer', 'paused', countdown.display.value);
});

const toggle = () => (countdown.running.value ? countdown.pause() : countdown.start());
</script>

<template>
    <FloatingPanel title="Timer" :icon="Timer" anchor="top-center" :width="260" @close="store.closePanel('timer')">
        <div class="space-y-4 p-4 text-center">
            <p
                class="font-mono text-5xl font-semibold tabular-nums"
                :class="{ 'text-yellow-600': countdown.nearEnd.value, 'text-destructive': countdown.timeOut.value }"
                role="timer"
                data-test="timer-display"
            >
                {{ countdown.display.value }}
            </p>
            <p v-if="countdown.timeOut.value" class="text-sm text-destructive" role="status">Die Zeit ist abgelaufen.</p>

            <div class="flex items-center justify-center gap-2">
                <Button :aria-label="countdown.running.value ? 'Pause' : 'Start'" @click="toggle">
                    <component :is="countdown.running.value ? Pause : Play" />{{ countdown.running.value ? 'Pause' : 'Start' }}
                </Button>
                <Button variant="secondary" size="icon" aria-label="Zurücksetzen" @click="countdown.reset()"><RotateCcw /></Button>
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
                    :disabled="countdown.running.value"
                    @change="applyMinutes"
                />
            </div>
        </div>
    </FloatingPanel>
</template>
