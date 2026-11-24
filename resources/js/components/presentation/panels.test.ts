import { useConsoleStore } from '@/stores/console';
import { usePresentationStore } from '@/stores/presentation';
import type { Board } from '@/types/presentation';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia, type Pinia } from 'pinia';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import BoardListPanel from './BoardListPanel.vue';
import ConsolePanel from './ConsolePanel.vue';
import NotesPanel from './NotesPanel.vue';
import PanelToolbar from './PanelToolbar.vue';
import TimerPanel from './TimerPanel.vue';

const board = (position: number, extra: Partial<Board> = {}): Board => ({
    id: position,
    position,
    title: `Board ${position}`,
    description: null,
    notes: null,
    day: 1,
    time: null,
    location: null,
    is_night: false,
    mood: null,
    simulator_state: null,
    assets: [],
    ...extra,
});

let pinia: Pinia;

beforeEach(() => {
    pinia = createPinia();
    setActivePinia(pinia);
});

describe('PanelToolbar', () => {
    it('shows one toggle per panel and reflects the open state', () => {
        const wrapper = mount(PanelToolbar, { global: { plugins: [pinia] } });

        const pressed = Object.fromEntries(wrapper.findAll('button').map((b) => [b.attributes('title'), b.attributes('aria-pressed')]));

        expect(pressed).toEqual({ Boards: 'false', Notizen: 'false', Simulator: 'true', Konsole: 'false', Timer: 'false', Fernsteuerung: 'false' });
    });

    it('toggles a panel on click', async () => {
        const wrapper = mount(PanelToolbar, { global: { plugins: [pinia] } });

        await wrapper.find('button[title="Konsole"]').trigger('click');

        expect(usePresentationStore().panels.console).toBe(true);
        expect(wrapper.find('button[title="Konsole"]').attributes('aria-pressed')).toBe('true');
    });
});

describe('BoardListPanel', () => {
    it('lists the boards, marks the current one and jumps on click', async () => {
        const store = usePresentationStore();
        store.setBoardCount(3);
        const wrapper = mount(BoardListPanel, { props: { boards: [board(1), board(2), board(3)] }, global: { plugins: [pinia] } });

        const items = wrapper.findAll('[data-test=board-list] button');
        expect(items).toHaveLength(3);
        expect(items[0].attributes('aria-current')).toBe('true');
        expect(items[1].attributes('aria-current')).toBeUndefined();

        await items[2].trigger('click');
        expect(store.scrollTarget).toBe(2);
    });

    it('closes through the panel close button', async () => {
        const store = usePresentationStore();
        store.togglePanel('boards');
        const wrapper = mount(BoardListPanel, { props: { boards: [board(1)] }, global: { plugins: [pinia] } });

        await wrapper.find('button[aria-label="Schliessen"]').trigger('click');

        expect(store.panels.boards).toBe(false);
    });
});

describe('NotesPanel', () => {
    it('shows the notes of the board, or a hint without notes', () => {
        const withNotes = mount(NotesPanel, { props: { board: board(2, { notes: 'Erst Wecker erklären' }) }, global: { plugins: [pinia] } });
        expect(withNotes.get('[data-test=notes]').text()).toContain('Erst Wecker erklären');

        const without = mount(NotesPanel, { props: { board: board(2) }, global: { plugins: [pinia] } });
        expect(without.get('[data-test=notes]').text()).toContain('Keine Notizen');
    });
});

describe('ConsolePanel', () => {
    it('shows a hint while empty, then the messages, and clears them', async () => {
        const log = useConsoleStore();
        const wrapper = mount(ConsolePanel, { global: { plugins: [pinia] } });
        expect(wrapper.get('[data-test=console]').text()).toContain('Noch keine Meldungen');

        log.log('board', 'Board 2 of 3', 2);
        await wrapper.vm.$nextTick();
        expect(wrapper.findAll('[data-test=console-row]')).toHaveLength(1);

        await wrapper.get('button.h-6.px-2').trigger('click');
        expect(log.messages).toHaveLength(0);
    });

    it('colours warnings and errors', async () => {
        const log = useConsoleStore();
        log.log('timer', 'time is up', null, 'warning');
        log.log('x', 'broken', null, 'error');
        const wrapper = mount(ConsolePanel, { global: { plugins: [pinia] } });

        const rows = wrapper.findAll('[data-test=console-row]');

        expect(rows[0].classes()).toContain('text-yellow-600');
        expect(rows[1].classes()).toContain('text-destructive');
    });
});

describe('TimerPanel', () => {
    beforeEach(() => vi.useFakeTimers());
    afterEach(() => vi.useRealTimers());

    const display = (wrapper: ReturnType<typeof mount>) => wrapper.get('[data-test=timer-display]').text();

    it('starts at five minutes and counts down after Start', async () => {
        const wrapper = mount(TimerPanel, { global: { plugins: [pinia] } });
        expect(display(wrapper)).toBe('05:00');

        await wrapper.get('button[aria-label="Start"]').trigger('click');
        vi.advanceTimersByTime(3000);
        await wrapper.vm.$nextTick();

        expect(display(wrapper)).toBe('04:57');
        expect(wrapper.find('button[aria-label="Pause"]').exists()).toBe(true);
    });

    it('takes a new duration in minutes', async () => {
        const wrapper = mount(TimerPanel, { global: { plugins: [pinia] } });
        const input = wrapper.get('#timer-minutes');

        await input.setValue('2');
        await input.trigger('change');

        expect(display(wrapper)).toBe('02:00');
    });

    it('warns near the end, reports time out and logs it', async () => {
        const wrapper = mount(TimerPanel, { global: { plugins: [pinia] } });
        const input = wrapper.get('#timer-minutes');
        await input.setValue('2');
        await input.trigger('change');

        await wrapper.get('button[aria-label="Start"]').trigger('click');
        vi.advanceTimersByTime(65_000);
        await wrapper.vm.$nextTick();
        expect(wrapper.get('[data-test=timer-display]').classes()).toContain('text-yellow-600');

        vi.advanceTimersByTime(60_000);
        await wrapper.vm.$nextTick();
        expect(wrapper.get('[data-test=timer-display]').classes()).toContain('text-destructive');
        expect(wrapper.text()).toContain('Die Zeit ist abgelaufen');
        expect(useConsoleStore().messages.map((m) => m.text)).toContain('time is up');
    });

    it('resets to the full duration', async () => {
        const wrapper = mount(TimerPanel, { global: { plugins: [pinia] } });

        await wrapper.get('button[aria-label="Start"]').trigger('click');
        vi.advanceTimersByTime(10_000);
        await wrapper.get('button[aria-label="Zurücksetzen"]').trigger('click');

        expect(display(wrapper)).toBe('05:00');
    });
});
