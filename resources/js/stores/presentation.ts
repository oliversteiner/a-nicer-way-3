import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { useConsoleStore } from './console';

export type PanelName = 'simulator' | 'boards' | 'notes' | 'console' | 'timer';

export const usePresentationStore = defineStore('presentation', () => {
    const consoleLog = useConsoleStore();

    const boardCount = ref(0);
    const currentIndex = ref(0);
    /** Set by navigation, consumed by the scroller. */
    const scrollTarget = ref<number | null>(null);
    /** Board the smooth scroll is heading to; lets quick key presses add up. */
    const pendingIndex = ref<number | null>(null);

    const panels = ref<Record<PanelName, boolean>>({ simulator: true, boards: false, notes: false, console: false, timer: false });

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
