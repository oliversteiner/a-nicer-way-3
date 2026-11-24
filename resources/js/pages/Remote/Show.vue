<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { RemoteApiError, sendCommand } from '@/lib/remoteApi';
import type { RemoteAction, RemoteState } from '@/types/remote';
import { Head } from '@inertiajs/vue3';
import { useConnectionStatus, useEchoPublic } from '@laravel/echo-vue';
import { useIntervalFn, useNow } from '@vueuse/core';
import { ChevronLeft, ChevronRight, Pause, Play, RotateCcw } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps<{
    token: string;
    journey: { title: string; boards: { position: number; title: string | null }[] };
}>();

const state = ref<RemoteState | null>(null);
const receivedAt = ref(Date.now());
const error = ref<string | null>(null);
const now = useNow({ interval: 500 });
const connection = useConnectionStatus();

useEchoPublic<RemoteState>(`remote.${props.token}.state`, '.remote.state', (payload) => {
    state.value = payload;
    receivedAt.value = Date.now();
    error.value = null;
});

async function send(action: RemoteAction, value?: number) {
    try {
        await sendCommand(props.token, action, value);
        error.value = null;
    } catch (caught) {
        error.value =
            caught instanceof RemoteApiError && caught.status === 404
                ? 'Die Sitzung ist abgelaufen. Scanne den QR-Code in der Präsentation erneut.'
                : 'Keine Verbindung zur Präsentation.';
    }
}

// Ask the presentation for its state until it answers (the channel may still be subscribing).
const { pause: stopHello, resume: startHello } = useIntervalFn(() => send('hello'), 2000, { immediate: false });
onMounted(() => {
    send('hello');
    startHello();
});
watch(state, (value) => value && stopHello());
watch(connection, (value) => value === 'connected' && send('hello'));

const remaining = computed(() => {
    const timer = state.value?.timer;
    if (!timer) return 0;
    if (!timer.running) return timer.remaining;
    return Math.max(timer.remaining - Math.floor((now.value.getTime() - receivedAt.value) / 1000), 0);
});
const display = computed(() => `${String(Math.floor(remaining.value / 60)).padStart(2, '0')}:${String(remaining.value % 60).padStart(2, '0')}`);
const nearEnd = computed(() => !!state.value && remaining.value > 0 && remaining.value <= 60 && state.value.timer.total > 60);
const timeOut = computed(() => !!state.value && state.value.timer.timeOut && remaining.value === 0);

const board = computed(() => state.value?.board ?? null);
const meta = computed(() =>
    [board.value?.day ? `Tag ${board.value.day}` : null, board.value?.time?.slice(0, 5), board.value?.location].filter(Boolean).join(' · '),
);
</script>

<template>
    <Head :title="`Fernsteuerung · ${journey.title}`" />

    <div class="flex min-h-screen flex-col gap-4 bg-background p-4 text-foreground">
        <header class="flex items-center justify-between gap-2">
            <h1 class="font-semibold">{{ journey.title }}</h1>
            <span class="text-xs" :class="state ? 'text-green-600' : 'text-muted-foreground'" role="status" data-test="connection">
                {{ state ? 'Verbunden' : 'Warte auf die Präsentation …' }}
            </span>
        </header>

        <p v-if="error" class="rounded-md border border-destructive/50 p-3 text-sm text-destructive" role="alert">{{ error }}</p>

        <section class="rounded-lg border bg-card p-4 text-card-foreground" data-test="board">
            <template v-if="board && state">
                <p class="text-sm text-muted-foreground">Board {{ board.position }} von {{ state.count }}</p>
                <h2 class="mt-1 text-xl font-semibold">{{ board.title }}</h2>
                <p v-if="meta" class="mt-1 text-sm text-muted-foreground">{{ meta }}</p>
            </template>
            <p v-else class="text-sm text-muted-foreground">Noch kein Board.</p>
        </section>

        <div class="grid grid-cols-2 gap-3">
            <Button
                size="lg"
                class="h-16 text-base"
                variant="secondary"
                aria-label="Zurück"
                :disabled="!state || state.index <= 0"
                @click="send('previous')"
            >
                <ChevronLeft class="size-6" />Zurück
            </Button>
            <Button size="lg" class="h-16 text-base" aria-label="Weiter" :disabled="!state || state.index >= state.count - 1" @click="send('next')">
                Weiter<ChevronRight class="size-6" />
            </Button>
        </div>

        <section class="space-y-2" aria-label="Boards">
            <h2 class="text-sm font-medium">Springen zu</h2>
            <div class="flex flex-wrap gap-2">
                <Button
                    v-for="item in journey.boards"
                    :key="item.position"
                    size="sm"
                    :variant="board?.position === item.position ? 'default' : 'secondary'"
                    class="min-w-10"
                    :aria-label="`Board ${item.position}${item.title ? ': ' + item.title : ''}`"
                    :aria-current="board?.position === item.position ? 'true' : undefined"
                    @click="send('goto', item.position)"
                >
                    {{ item.position }}
                </Button>
            </div>
        </section>

        <section class="space-y-3 rounded-lg border bg-card p-4 text-center" aria-label="Timer">
            <p
                class="font-mono text-5xl font-semibold tabular-nums"
                :class="{ 'text-yellow-600': nearEnd, 'text-destructive': timeOut }"
                role="timer"
                data-test="timer"
            >
                {{ display }}
            </p>
            <div class="flex items-center justify-center gap-2">
                <Button :disabled="!state" :aria-label="state?.timer.running ? 'Pause' : 'Start'" @click="send('timer-toggle')">
                    <component :is="state?.timer.running ? Pause : Play" />{{ state?.timer.running ? 'Pause' : 'Start' }}
                </Button>
                <Button variant="secondary" size="icon" :disabled="!state" aria-label="Zurücksetzen" @click="send('timer-reset')"
                    ><RotateCcw
                /></Button>
            </div>
        </section>

        <section v-if="board" class="space-y-2" aria-label="Notizen">
            <h2 class="text-sm font-medium">Notizen</h2>
            <p class="whitespace-pre-line text-sm text-muted-foreground" data-test="notes">{{ board.notes || 'Keine Notizen zu diesem Board.' }}</p>
        </section>
    </div>
</template>
