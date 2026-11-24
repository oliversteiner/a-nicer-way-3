import { endPairing, pairPresentation, RemoteApiError, sendState } from '@/lib/remoteApi';
import type { Pairing, RemoteCommand, RemoteState } from '@/types/remote';
import { echo } from '@laravel/echo-vue';
import { useDebounceFn } from '@vueuse/core';
import { defineStore } from 'pinia';
import { ref } from 'vue';
import { useConsoleStore } from './console';
import { usePresentationStore } from './presentation';

export type RemoteStatus = 'idle' | 'pairing' | 'ready' | 'error';

const storageKey = (slug: string) => `remote-pairing:${slug}`;

/** Presenter side of the remote control: pairing, incoming commands, published state. */
export const useRemoteStore = defineStore('remote', () => {
    const presentation = usePresentationStore();
    const log = useConsoleStore();

    const pairing = ref<Pairing | null>(null);
    const status = ref<RemoteStatus>('idle');
    /** True once a remote control has said hello. */
    const connected = ref(false);

    let slug = '';
    let buildState: (() => RemoteState) | null = null;
    let channelName: string | null = null;

    /** Where the current state of the presentation comes from. */
    function setStateSource(source: () => RemoteState) {
        buildState = source;
    }

    function handleCommand(command: RemoteCommand) {
        log.log('remote', command.action, command.value);

        switch (command.action) {
            case 'next':
                presentation.goToNext();
                break;
            case 'previous':
                presentation.goToPrevious();
                break;
            case 'goto':
                if (command.value !== null) presentation.goTo(command.value - 1);
                break;
            case 'timer-toggle':
                presentation.timerToggle();
                break;
            case 'timer-reset':
                presentation.timerReset();
                break;
            case 'character-select':
                if (command.value !== null) presentation.selectCharacter(command.value);
                break;
            case 'character-pose':
                if (command.pose) presentation.setPose(command.pose);
                break;
            case 'hello':
                connected.value = true;
                break;
        }

        if (command.action === 'hello') publishNow();
    }

    function listen(token: string) {
        stopListening();
        channelName = `remote.${token}.commands`;
        echo().channel(channelName).listen('.remote.command', handleCommand);
    }

    function stopListening() {
        if (channelName) echo().leave(channelName);
        channelName = null;
    }

    async function publishNow() {
        if (!pairing.value || !buildState) return;

        try {
            await sendState(pairing.value.remote_token, pairing.value.presenter_token, buildState());
        } catch (error) {
            // The pairing expired or was revoked: start a new one.
            if (error instanceof RemoteApiError && (error.status === 403 || error.status === 404)) {
                log.log('remote', 'pairing expired', null, 'warning');
                await pair(slug);
            }
        }
    }

    /** Publishes the state shortly after the last change. */
    const publish = useDebounceFn(publishNow, 150);

    /** Start a new pairing for this presentation. */
    async function pair(journeySlug: string) {
        slug = journeySlug;
        status.value = 'pairing';
        connected.value = false;
        const previous = pairing.value;

        try {
            const created = await pairPresentation(journeySlug);
            pairing.value = created;
            sessionStorage.setItem(storageKey(journeySlug), JSON.stringify(created));
            listen(created.remote_token);
            status.value = 'ready';
            log.log('remote', 'paired', null, 'success');

            // A new pairing replaces the old one: its remote control stops working.
            if (previous) await endPairing(previous.remote_token, previous.presenter_token).catch(() => undefined);
        } catch {
            pairing.value = null;
            status.value = 'error';
            log.log('remote', 'pairing failed', null, 'error');
        }
    }

    /** Pick up a pairing from this browser session after a reload. */
    function resume(journeySlug: string) {
        slug = journeySlug;
        if (pairing.value) return;

        const saved = sessionStorage.getItem(storageKey(journeySlug));
        if (!saved) return;

        try {
            pairing.value = JSON.parse(saved) as Pairing;
            listen(pairing.value.remote_token);
            status.value = 'ready';
            // Bring a waiting remote control up to date after the reload.
            publish();
        } catch {
            sessionStorage.removeItem(storageKey(journeySlug));
        }
    }

    /** End the pairing; the remote control stops working. */
    async function unpair() {
        const current = pairing.value;
        stopListening();
        pairing.value = null;
        connected.value = false;
        status.value = 'idle';
        sessionStorage.removeItem(storageKey(slug));

        if (current) await endPairing(current.remote_token, current.presenter_token).catch(() => undefined);
    }

    return { pairing, status, connected, setStateSource, handleCommand, pair, resume, unpair, publish, publishNow };
});
