import { RemoteApiError } from '@/lib/remoteApi';
import type { RemoteState } from '@/types/remote';
import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';
import Show from './Show.vue';

const mocks = vi.hoisted(() => ({ sendCommand: vi.fn(), onState: null as null | ((state: RemoteState) => void) }));

vi.mock('@laravel/echo-vue', () => ({
    useEchoPublic: (_channel: string, _event: string, callback: (state: RemoteState) => void) => {
        mocks.onState = callback;
    },
    useConnectionStatus: () => ref('connected'),
}));

vi.mock('@inertiajs/vue3', () => ({ Head: { render: () => null } }));

vi.mock('@/lib/remoteApi', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@/lib/remoteApi')>()),
    sendCommand: mocks.sendCommand,
}));

const props = { token: 'rt', journey: { title: 'Reise', boards: [1, 2, 3].map((position) => ({ position, title: `B${position}` })) } };

const state = (overrides: Partial<RemoteState> = {}): RemoteState => ({
    index: 1,
    count: 3,
    board: { position: 2, title: 'Zug', notes: 'Erst Wecker', day: 1, time: '08:00:00', location: 'Bahn' },
    characters: [
        { id: 7, name: 'Kevin' },
        { id: 8, name: 'Bus' },
    ],
    character_id: 7,
    character_pose: 'idle',
    timer: { remaining: 120, total: 300, running: false, nearEnd: false, timeOut: false },
    ...overrides,
});

const open = () => mount(Show, { props });
const command = (wrapper: ReturnType<typeof open>, label: string) => wrapper.get(`button[aria-label="${label}"]`);

beforeEach(() => {
    vi.useFakeTimers();
    mocks.sendCommand.mockReset().mockResolvedValue(null);
    mocks.onState = null;
});
afterEach(() => vi.useRealTimers());

describe('remote control page', () => {
    it('says hello on open and keeps asking until the presentation answers', async () => {
        const wrapper = open();
        await flushPromises();
        expect(mocks.sendCommand).toHaveBeenCalledWith('rt', 'hello', undefined, undefined);

        mocks.sendCommand.mockClear();
        await vi.advanceTimersByTimeAsync(4100);
        expect(mocks.sendCommand.mock.calls.filter((c) => c[1] === 'hello').length).toBeGreaterThanOrEqual(2);

        mocks.onState!(state());
        await flushPromises();
        mocks.sendCommand.mockClear();
        await vi.advanceTimersByTimeAsync(6000);
        expect(mocks.sendCommand).not.toHaveBeenCalled();
        expect(wrapper.get('[data-test=connection]').text()).toBe('Verbunden');
    });

    it('shows the board, its details and its notes', async () => {
        const wrapper = open();
        mocks.onState!(state());
        await flushPromises();

        expect(wrapper.get('[data-test=board]').text()).toContain('Board 2 von 3');
        expect(wrapper.get('[data-test=board]').text()).toContain('Zug');
        expect(wrapper.get('[data-test=board]').text()).toContain('Tag 1 · 08:00 · Bahn');
        expect(wrapper.get('[data-test=notes]').text()).toBe('Erst Wecker');
    });

    it('sends next, previous and goto', async () => {
        const wrapper = open();
        mocks.onState!(state());
        await flushPromises();
        mocks.sendCommand.mockClear();

        await command(wrapper, 'Weiter').trigger('click');
        await command(wrapper, 'Zurück').trigger('click');
        await wrapper.get('button[aria-label="Board 3: B3"]').trigger('click');

        expect(mocks.sendCommand.mock.calls).toEqual([
            ['rt', 'next', undefined, undefined],
            ['rt', 'previous', undefined, undefined],
            ['rt', 'goto', 3, undefined],
        ]);
    });

    it('disables going past the first and the last board', async () => {
        const wrapper = open();
        mocks.onState!(state({ index: 0 }));
        await flushPromises();
        expect(command(wrapper, 'Zurück').attributes('disabled')).toBeDefined();

        mocks.onState!(state({ index: 2 }));
        await flushPromises();
        expect(command(wrapper, 'Weiter').attributes('disabled')).toBeDefined();
    });

    it('shows the timer and counts down on its own while it runs', async () => {
        const wrapper = open();
        mocks.onState!(state({ timer: { remaining: 120, total: 300, running: true, nearEnd: false, timeOut: false } }));
        await flushPromises();
        expect(wrapper.get('[data-test=timer]').text()).toBe('02:00');

        await vi.advanceTimersByTimeAsync(5000);

        expect(wrapper.get('[data-test=timer]').text()).toBe('01:55');
    });

    it('warns near the end and on time out', async () => {
        const wrapper = open();
        mocks.onState!(state({ timer: { remaining: 30, total: 300, running: false, nearEnd: true, timeOut: false } }));
        await flushPromises();
        expect(wrapper.get('[data-test=timer]').classes()).toContain('text-yellow-600');

        mocks.onState!(state({ timer: { remaining: 0, total: 300, running: false, nearEnd: false, timeOut: true } }));
        await flushPromises();
        expect(wrapper.get('[data-test=timer]').classes()).toContain('text-destructive');
    });

    it('controls the timer', async () => {
        const wrapper = open();
        mocks.onState!(state());
        await flushPromises();
        mocks.sendCommand.mockClear();

        await command(wrapper, 'Start').trigger('click');
        await command(wrapper, 'Zurücksetzen').trigger('click');

        expect(mocks.sendCommand.mock.calls.map((c) => c[1])).toEqual(['timer-toggle', 'timer-reset']);
    });

    it('explains an expired session and a lost connection', async () => {
        const wrapper = open();
        mocks.onState!(state());
        await flushPromises();

        mocks.sendCommand.mockRejectedValueOnce(new RemoteApiError(404));
        await command(wrapper, 'Weiter').trigger('click');
        await flushPromises();
        expect(wrapper.get('[role=alert]').text()).toContain('abgelaufen');

        mocks.sendCommand.mockRejectedValueOnce(new RemoteApiError(500));
        await command(wrapper, 'Weiter').trigger('click');
        await flushPromises();
        expect(wrapper.get('[role=alert]').text()).toContain('Keine Verbindung');
    });
    it('lists the characters and marks the current one and its pose', async () => {
        const wrapper = open();
        mocks.onState!(state({ character_id: 8, character_pose: 'go' }));
        await flushPromises();

        const pressed = (group: string) =>
            Object.fromEntries(wrapper.findAll(`[aria-label="${group}"] button`).map((b) => [b.text(), b.attributes('aria-pressed')]));

        expect(pressed('Figur wählen')).toEqual({ Kevin: 'false', Bus: 'true' });
        expect(pressed('Aktion')).toEqual({ Stehen: 'false', Gehen: 'true', Pause: 'false', Grüssen: 'false' });
    });

    it('selects a character and sets a pose', async () => {
        const wrapper = open();
        mocks.onState!(state());
        await flushPromises();
        mocks.sendCommand.mockClear();

        await wrapper.findAll('[aria-label="Figur wählen"] button')[1].trigger('click');
        await wrapper.findAll('[aria-label="Aktion"] button')[3].trigger('click');

        expect(mocks.sendCommand.mock.calls).toEqual([
            ['rt', 'character-select', 8, undefined],
            ['rt', 'character-pose', undefined, 'greet'],
        ]);
    });

    it('hides the character section when the journey has no characters', async () => {
        const wrapper = open();
        mocks.onState!(state({ characters: [], character_id: null }));
        await flushPromises();

        expect(wrapper.find('[data-test=character]').exists()).toBe(false);
    });
});
