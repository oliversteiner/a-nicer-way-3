import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';
import { useConsoleStore } from './console';

describe('console store', () => {
    beforeEach(() => setActivePinia(createPinia()));

    it('logs messages with increasing ids and a default status', () => {
        const store = useConsoleStore();

        store.log('board', 'Board 2 of 5', 2);
        store.log('timer', 'finished', null, 'warning');

        expect(store.messages.map((m) => m.id)).toEqual([1, 2]);
        expect(store.messages[0]).toMatchObject({ tag: 'board', value: 2, status: 'info' });
        expect(store.messages[1].status).toBe('warning');
    });

    it('clears the log but keeps counting ids', () => {
        const store = useConsoleStore();
        store.log('a', 'one');

        store.clear();
        store.log('a', 'two');

        expect(store.messages).toHaveLength(1);
        expect(store.messages[0].id).toBe(2);
    });

    it('keeps only the latest 200 messages', () => {
        const store = useConsoleStore();

        for (let i = 1; i <= 250; i++) store.log('t', `m${i}`);

        expect(store.messages).toHaveLength(200);
        expect(store.messages[0].text).toBe('m51');
        expect(store.messages[199].text).toBe('m250');
    });
});
