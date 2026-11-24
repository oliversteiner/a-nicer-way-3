import { useCountdown } from '@/composables/useCountdown';
import type { CharacterPose } from '@/types/presentation';
import { defineStore } from 'pinia';
import { computed, ref, watch } from 'vue';
import { useConsoleStore } from './console';

export type PanelName = 'simulator' | 'boards' | 'notes' | 'console' | 'timer' | 'remote' | 'character';

export const usePresentationStore = defineStore('presentation', () => {
    const consoleLog = useConsoleStore();

    const boardCount = ref(0);
    const currentIndex = ref(0);
    /** Set by navigation, consumed by the scroller. */
    const scrollTarget = ref<number | null>(null);
    /** Board the smooth scroll is heading to; lets quick key presses add up. */
    const pendingIndex = ref<number | null>(null);

    const panels = ref<Record<PanelName, boolean>>({
        simulator: true,
        boards: false,
        notes: false,
        console: false,
        timer: false,
        remote: false,
        character: false,
    });

    function setPanel(name: PanelName, open: boolean) {
        if (panels.value[name] === open) return;
        panels.value[name] = open;
        if (name === 'simulator' && !open) simulatorSolo.value = false;
        consoleLog.log('panel', `${name} ${open ? 'opened' : 'closed'}`);
    }

    const togglePanel = (name: PanelName) => setPanel(name, !panels.value[name]);
    const closePanel = (name: PanelName) => setPanel(name, false);

    const simulatorSolo = ref(false);
    const simulatorHeight = ref(560);

    const SIMULATOR_MIN = 360;
    const SIMULATOR_MAX = 800;
    const SIMULATOR_STEP = 80;

    function resizeSimulator(delta: number) {
        simulatorHeight.value = Math.min(Math.max(simulatorHeight.value + delta, SIMULATOR_MIN), SIMULATOR_MAX);
    }

    const simulatorBigger = () => resizeSimulator(SIMULATOR_STEP);
    const simulatorSmaller = () => resizeSimulator(-SIMULATOR_STEP);

    // The talk timer lives here (not in its panel) so the remote control can drive it too.
    const countdown = useCountdown({
        seconds: 300,
        warningSeconds: 60,
        onFinish: () => consoleLog.log('timer', 'time is up', null, 'warning'),
    });

    watch(countdown.running, (running, wasRunning) => {
        if (running) consoleLog.log('timer', 'started', countdown.display.value);
        else if (wasRunning && !countdown.timeOut.value) consoleLog.log('timer', 'paused', countdown.display.value);
    });

    const timerToggle = () => (countdown.running.value ? countdown.pause() : countdown.start());

    // Character layer: which figure is on stage and what it is doing.
    const characterIds = ref<number[]>([]);
    const characterId = ref<number | null>(null);
    const characterPose = ref<CharacterPose>('idle');
    const GREET_SECONDS = 2.5;
    let greetTimer: ReturnType<typeof setTimeout> | null = null;

    function clearGreetTimer() {
        if (greetTimer !== null) clearTimeout(greetTimer);
        greetTimer = null;
    }

    /** Called with the journey's characters: keeps the selection if it still exists, else picks the first. */
    function setCharacters(ids: number[]) {
        characterIds.value = ids;
        if (characterId.value === null || !ids.includes(characterId.value)) characterId.value = ids[0] ?? null;
    }

    function selectCharacter(id: number) {
        if (!characterIds.value.includes(id)) return;

        clearGreetTimer();
        characterId.value = id;
        characterPose.value = 'idle';
        consoleLog.log('character', 'selected', id);
    }

    function setPose(pose: CharacterPose) {
        clearGreetTimer();
        characterPose.value = pose;
        consoleLog.log('character', pose);

        // A greeting is short: the figure goes back to standing.
        if (pose === 'greet') {
            greetTimer = setTimeout(() => {
                if (characterPose.value === 'greet') characterPose.value = 'idle';
            }, GREET_SECONDS * 1000);
        }
    }

    const isFirst = computed(() => currentIndex.value <= 0);
    const isLast = computed(() => currentIndex.value >= boardCount.value - 1);

    function setBoardCount(count: number) {
        boardCount.value = count;
        currentIndex.value = Math.min(currentIndex.value, Math.max(count - 1, 0));
    }

    function setCurrent(index: number) {
        const previous = currentIndex.value;
        currentIndex.value = Math.min(Math.max(index, 0), Math.max(boardCount.value - 1, 0));
        if (currentIndex.value !== previous)
            consoleLog.log('board', `Board ${currentIndex.value + 1} of ${boardCount.value}`, currentIndex.value + 1);
        if (pendingIndex.value === currentIndex.value) pendingIndex.value = null;
    }

    function goTo(index: number) {
        const target = Math.min(Math.max(index, 0), Math.max(boardCount.value - 1, 0));
        pendingIndex.value = target === currentIndex.value ? null : target;
        scrollTarget.value = target;
    }

    const goToNext = () => goTo((pendingIndex.value ?? currentIndex.value) + 1);
    const goToPrevious = () => goTo((pendingIndex.value ?? currentIndex.value) - 1);

    return {
        panels,
        characterIds,
        characterId,
        characterPose,
        setCharacters,
        selectCharacter,
        setPose,
        timerRemaining: countdown.remaining,
        timerTotal: countdown.total,
        timerRunning: countdown.running,
        timerDisplay: countdown.display,
        timerNearEnd: countdown.nearEnd,
        timerTimeOut: countdown.timeOut,
        timerStart: countdown.start,
        timerPause: countdown.pause,
        timerReset: countdown.reset,
        timerSetDuration: countdown.setDuration,
        timerToggle,
        togglePanel,
        closePanel,
        simulatorSolo,
        simulatorHeight,
        simulatorBigger,
        simulatorSmaller,
        boardCount,
        currentIndex,
        scrollTarget,
        isFirst,
        isLast,
        setBoardCount,
        setCurrent,
        goTo,
        goToNext,
        goToPrevious,
    };
});
