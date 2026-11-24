import { computed, onScopeDispose, ref } from 'vue';

interface CountdownOptions {
    /** Initial duration in seconds. */
    seconds?: number;
    /** The countdown counts as "near the end" at or below this many seconds. */
    warningSeconds?: number;
    /** Called once when the countdown reaches zero. */
    onFinish?: () => void;
    /** Clock, replaceable in tests. */
    now?: () => number;
}

const MAX_SECONDS = 999 * 60 + 59;

/** Countdown timer for a talk. Computes from the clock, so it does not drift when ticks are late. */
export function useCountdown(options: CountdownOptions = {}) {
    const now = options.now ?? Date.now;
    const warningSeconds = options.warningSeconds ?? 60;

    const total = ref(clamp(options.seconds ?? 300));
    const remaining = ref(total.value);
    const running = ref(false);
    const finished = ref(false);

    let endsAt = 0;
    let handle: ReturnType<typeof setInterval> | null = null;

    function clamp(seconds: number) {
        return Math.min(Math.max(Math.floor(Number.isFinite(seconds) ? seconds : 0), 0), MAX_SECONDS);
    }

    function stopTicking() {
        if (handle !== null) clearInterval(handle);
        handle = null;
    }

    function tick() {
        remaining.value = Math.max(Math.ceil((endsAt - now()) / 1000), 0);
        if (remaining.value === 0) {
            stopTicking();
            running.value = false;
            finished.value = true;
            options.onFinish?.();
        }
    }

    function start() {
        if (running.value) return;
        if (remaining.value <= 0) remaining.value = total.value;
        if (remaining.value <= 0) return;

        finished.value = false;
        running.value = true;
        endsAt = now() + remaining.value * 1000;
        handle = setInterval(tick, 250);
    }

    function pause() {
        if (!running.value) return;
        tick();
        stopTicking();
        running.value = false;
    }

    function reset() {
        stopTicking();
        running.value = false;
        finished.value = false;
        remaining.value = total.value;
    }

    /** Set a new duration; stops a running countdown. */
    function setDuration(seconds: number) {
        stopTicking();
        running.value = false;
        finished.value = false;
        total.value = clamp(seconds);
        remaining.value = total.value;
    }

    const minutes = computed(() => Math.floor(remaining.value / 60));
    const seconds = computed(() => remaining.value % 60);
    const display = computed(() => `${String(minutes.value).padStart(2, '0')}:${String(seconds.value).padStart(2, '0')}`);
    const nearEnd = computed(() => remaining.value > 0 && remaining.value <= warningSeconds && total.value > warningSeconds);
    const timeOut = computed(() => finished.value && remaining.value === 0);

    onScopeDispose(stopTicking);

    return { total, remaining, running, finished, minutes, seconds, display, nearEnd, timeOut, start, pause, reset, setDuration };
}
