import { createPinia, setActivePinia } from 'pinia';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useConsoleStore } from './console';
import { usePresentationStore } from './presentation';

describe('presentation store', () => {
    beforeEach(() => setActivePinia(createPinia()));

    it('starts with only the simulator open', () => {
        const store = usePresentationStore();

        expect(store.panels).toEqual({ simulator: true, boards: false, notes: false, console: false, timer: false, remote: false, character: false });
    });

    it('toggles panels and logs the change', () => {
        const store = usePresentationStore();
        const log = useConsoleStore();

        store.togglePanel('console');
        expect(store.panels.console).toBe(true);
        store.closePanel('console');
        expect(store.panels.console).toBe(false);

        expect(log.messages.map((m) => m.text)).toEqual(['console opened', 'console closed']);
    });

    it('does not log when a panel is already in the requested state', () => {
        const store = usePresentationStore();
        const log = useConsoleStore();

        store.closePanel('timer');

        expect(log.messages).toHaveLength(0);
    });

    it('leaves solo mode when the simulator closes', () => {
        const store = usePresentationStore();
        store.simulatorSolo = true;

        store.closePanel('simulator');

        expect(store.simulatorSolo).toBe(false);
    });

    it('clamps the current board and logs real changes only', () => {
        const store = usePresentationStore();
        const log = useConsoleStore();
        store.setBoardCount(3);

        store.setCurrent(1);
        store.setCurrent(1);
        store.setCurrent(99);

        expect(store.currentIndex).toBe(2);
        expect(log.messages.map((m) => m.value)).toEqual([2, 3]);
    });

    it('adds up quick next presses while a scroll is pending', () => {
        const store = usePresentationStore();
        store.setBoardCount(5);

        store.goToNext();
        store.goToNext();
        store.goToNext();

        expect(store.scrollTarget).toBe(3);
    });

    it('limits the simulator size', () => {
        const store = usePresentationStore();

        for (let i = 0; i < 10; i++) store.simulatorBigger();
        expect(store.simulatorHeight).toBe(800);
        for (let i = 0; i < 10; i++) store.simulatorSmaller();
        expect(store.simulatorHeight).toBe(360);
    });
    describe('character layer', () => {
        beforeEach(() => vi.useFakeTimers());
        afterEach(() => vi.useRealTimers());

        it('picks the first character and keeps a still valid selection', () => {
            const store = usePresentationStore();

            store.setCharacters([4, 5]);
            expect(store.characterId).toBe(4);

            store.selectCharacter(5);
            store.setCharacters([4, 5]);
            expect(store.characterId).toBe(5);

            store.setCharacters([9]);
            expect(store.characterId).toBe(9);
        });

        it('has no selection without characters', () => {
            const store = usePresentationStore();

            store.setCharacters([]);

            expect(store.characterId).toBeNull();
        });

        it('stands again when another character is selected', () => {
            const store = usePresentationStore();
            store.setCharacters([1, 2]);
            store.setPose('go');

            store.selectCharacter(2);

            expect(store.characterPose).toBe('idle');
        });

        it('keeps go and pause until changed', () => {
            const store = usePresentationStore();

            store.setPose('go');
            vi.advanceTimersByTime(60_000);
            expect(store.characterPose).toBe('go');

            store.setPose('pause');
            vi.advanceTimersByTime(60_000);
            expect(store.characterPose).toBe('pause');
        });

        it('returns from greeting to standing after a short time', () => {
            const store = usePresentationStore();

            store.setPose('greet');
            expect(store.characterPose).toBe('greet');
            vi.advanceTimersByTime(2600);

            expect(store.characterPose).toBe('idle');
        });

        it('does not let an old greeting end a newer pose', () => {
            const store = usePresentationStore();

            store.setPose('greet');
            vi.advanceTimersByTime(1000);
            store.setPose('go');
            vi.advanceTimersByTime(5000);

            expect(store.characterPose).toBe('go');
        });

        it('logs what the character does', () => {
            const store = usePresentationStore();
            const log = useConsoleStore();

            store.setPose('pause');

            expect(log.messages.at(-1)).toMatchObject({ tag: 'character', text: 'pause' });
        });
    });
});
