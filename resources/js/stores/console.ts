import { defineStore } from 'pinia';
import { ref } from 'vue';

export type ConsoleStatus = 'info' | 'success' | 'warning' | 'error';

export interface ConsoleMessage {
    id: number;
    tag: string;
    text: string;
    value: string | number | boolean | null;
    status: ConsoleStatus;
}

const MAX_MESSAGES = 200;

/** Event log shown in the console panel: board changes, panel toggles, timer events. */
export const useConsoleStore = defineStore('console', () => {
    const messages = ref<ConsoleMessage[]>([]);
    let nextId = 1;

    function log(tag: string, text: string, value: ConsoleMessage['value'] = null, status: ConsoleStatus = 'info') {
        messages.value.push({ id: nextId++, tag, text, value, status });
        if (messages.value.length > MAX_MESSAGES) messages.value.splice(0, messages.value.length - MAX_MESSAGES);
    }

    function clear() {
        messages.value = [];
    }

    return { messages, log, clear };
});
