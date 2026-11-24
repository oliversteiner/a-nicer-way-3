import { RemoteApiError } from '@/lib/remoteApi';
import type { RemoteState } from '@/types/remote';
import { createPinia, setActivePinia } from 'pinia';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useConsoleStore } from './console';
import { usePresentationStore } from './presentation';
import { useRemoteStore } from './remote';

const mocks = vi.hoisted(() => ({
    listen: vi.fn(),
    channel: vi.fn(),
    leave: vi.fn(),
    pairPresentation: vi.fn(),
    sendState: vi.fn(),
    endPairing: vi.fn(),
}));

vi.mock('@laravel/echo-vue', () => ({
    echo: () => ({ channel: mocks.channel, leave: mocks.leave }),
}));

vi.mock('@/lib/remoteApi', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@/lib/remoteApi')>()),
    pairPresentation: mocks.pairPresentation,
    sendState: mocks.sendState,
    endPairing: mocks.endPairing,
}));

const pairing = { remote_token: 'rt', presenter_token: 'pt', remote_url: 'http://x.test/remote/rt' };
const state: RemoteState = { index: 0, count: 3, board: null, timer: { remaining: 300, total: 300, running: false, nearEnd: false, timeOut: false } };

beforeEach(() => {
    setActivePinia(createPinia());
    sessionStorage.clear();
    Object.values(mocks).forEach((mock) => mock.mockReset());
    mocks.channel.mockReturnValue({ listen: mocks.listen });
    mocks.pairPresentation.mockResolvedValue(pairing);
    mocks.sendState.mockResolvedValue(null);
    mocks.endPairing.mockResolvedValue(null);
});

afterEach(() => vi.useRealTimers());

describe('remote store', () => {
    it('pairs, listens on the command channel and remembers the pairing', async () => {
        const remote = useRemoteStore();

        await remote.pair('demo');

        expect(remote.status).toBe('ready');
        expect(remote.pairing).toEqual(pairing);
        expect(mocks.channel).toHaveBeenCalledWith('remote.rt.commands');
        expect(mocks.listen).toHaveBeenCalledWith('.remote.command', expect.any(Function));
        expect(JSON.parse(sessionStorage.getItem('remote-pairing:demo')!)).toEqual(pairing);
    });

    it('reports a failed pairing', async () => {
        mocks.pairPresentation.mockRejectedValue(new RemoteApiError(500));
        const remote = useRemoteStore();

        await remote.pair('demo');

        expect(remote.status).toBe('error');
        expect(remote.pairing).toBeNull();
        expect(useConsoleStore().messages.at(-1)?.status).toBe('error');
    });

    it('pairs again with new tokens and leaves the old channel', async () => {
        const remote = useRemoteStore();
        await remote.pair('demo');
        mocks.pairPresentation.mockResolvedValue({ ...pairing, remote_token: 'rt2' });

        await remote.pair('demo');

        expect(mocks.leave).toHaveBeenCalledWith('remote.rt.commands');
        expect(mocks.channel).toHaveBeenLastCalledWith('remote.rt2.commands');
    });

    it('ends the old pairing when it pairs again', async () => {
        const remote = useRemoteStore();
        await remote.pair('demo');
        mocks.pairPresentation.mockResolvedValue({ ...pairing, remote_token: 'rt2', presenter_token: 'pt2' });

        await remote.pair('demo');

        expect(mocks.endPairing).toHaveBeenCalledWith('rt', 'pt');
        expect(mocks.endPairing).not.toHaveBeenCalledWith('rt2', 'pt2');
    });

    it('resumes a saved pairing without asking the server and refreshes the remote', async () => {
        vi.useFakeTimers();
        sessionStorage.setItem('remote-pairing:demo', JSON.stringify(pairing));
        const remote = useRemoteStore();
        remote.setStateSource(() => state);

        remote.resume('demo');
        await vi.advanceTimersByTimeAsync(200);

        expect(remote.status).toBe('ready');
        expect(mocks.pairPresentation).not.toHaveBeenCalled();
        expect(mocks.channel).toHaveBeenCalledWith('remote.rt.commands');
        expect(mocks.sendState).toHaveBeenCalledWith('rt', 'pt', state);
    });

    it('ignores a damaged saved pairing', () => {
        sessionStorage.setItem('remote-pairing:demo', '{broken');
        const remote = useRemoteStore();

        remote.resume('demo');

        expect(remote.status).toBe('idle');
        expect(sessionStorage.getItem('remote-pairing:demo')).toBeNull();
    });

    it('moves through the boards on next, previous and goto', () => {
        const presentation = usePresentationStore();
        presentation.setBoardCount(5);
        const remote = useRemoteStore();

        remote.handleCommand({ action: 'next', value: null });
        expect(presentation.scrollTarget).toBe(1);

        remote.handleCommand({ action: 'goto', value: 4 });
        expect(presentation.scrollTarget).toBe(3);

        presentation.setCurrent(3);
        remote.handleCommand({ action: 'previous', value: null });
        expect(presentation.scrollTarget).toBe(2);
    });

    it('drives the timer', () => {
        const presentation = usePresentationStore();
        const remote = useRemoteStore();

        remote.handleCommand({ action: 'timer-toggle', value: null });
        expect(presentation.timerRunning).toBe(true);

        remote.handleCommand({ action: 'timer-toggle', value: null });
        expect(presentation.timerRunning).toBe(false);

        remote.handleCommand({ action: 'timer-reset', value: null });
        expect(presentation.timerRemaining).toBe(300);
    });

    it('answers hello with the current state and marks the remote as connected', async () => {
        const remote = useRemoteStore();
        await remote.pair('demo');
        remote.setStateSource(() => state);

        remote.handleCommand({ action: 'hello', value: null });

        expect(remote.connected).toBe(true);
        expect(mocks.sendState).toHaveBeenCalledWith('rt', 'pt', state);
    });

    it('logs every command in the console', () => {
        const remote = useRemoteStore();

        remote.handleCommand({ action: 'goto', value: 2 });

        expect(useConsoleStore().messages.at(-1)).toMatchObject({ tag: 'remote', text: 'goto', value: 2 });
    });

    it('does nothing when publishing without a pairing', async () => {
        const remote = useRemoteStore();
        remote.setStateSource(() => state);

        await remote.publishNow();

        expect(mocks.sendState).not.toHaveBeenCalled();
    });

    it('sends only the last of several quick state changes', async () => {
        vi.useFakeTimers();
        const remote = useRemoteStore();
        await remote.pair('demo');
        remote.setStateSource(() => state);

        remote.publish();
        remote.publish();
        remote.publish();
        await vi.advanceTimersByTimeAsync(200);

        expect(mocks.sendState).toHaveBeenCalledTimes(1);
    });

    it('starts a new pairing when the server no longer knows it', async () => {
        const remote = useRemoteStore();
        await remote.pair('demo');
        remote.setStateSource(() => state);
        mocks.pairPresentation.mockResolvedValue({ ...pairing, remote_token: 'fresh' });
        mocks.sendState.mockRejectedValueOnce(new RemoteApiError(403));

        await remote.publishNow();

        expect(remote.pairing?.remote_token).toBe('fresh');
    });

    it('unpairs: leaves the channel, forgets the pairing and tells the server', async () => {
        const remote = useRemoteStore();
        await remote.pair('demo');

        await remote.unpair();

        expect(mocks.leave).toHaveBeenCalledWith('remote.rt.commands');
        expect(remote.pairing).toBeNull();
        expect(remote.status).toBe('idle');
        expect(sessionStorage.getItem('remote-pairing:demo')).toBeNull();
        expect(mocks.endPairing).toHaveBeenCalledWith('rt', 'pt');
    });
});
