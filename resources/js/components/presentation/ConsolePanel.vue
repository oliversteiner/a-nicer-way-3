<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { useConsoleStore, type ConsoleStatus } from '@/stores/console';
import { usePresentationStore } from '@/stores/presentation';
import { Terminal } from 'lucide-vue-next';
import { nextTick, ref, watch } from 'vue';
import FloatingPanel from './FloatingPanel.vue';

const store = usePresentationStore();
const log = useConsoleStore();
const list = ref<HTMLElement | null>(null);

const statusClass: Record<ConsoleStatus, string> = {
    info: 'text-foreground',
    success: 'text-green-600',
    warning: 'text-yellow-600',
    error: 'text-destructive',
};

// Keep the newest message in view.
watch(
    () => log.messages.length,
    async () => {
        await nextTick();
        if (list.value) list.value.scrollTop = list.value.scrollHeight;
    },
    { flush: 'post' },
);
</script>

<template>
    <FloatingPanel title="Konsole" :icon="Terminal" anchor="bottom-left" :width="420" @close="store.closePanel('console')">
        <template #actions>
            <Button v-if="log.messages.length" variant="ghost" size="sm" class="h-6 px-2 text-xs" @click="log.clear()">Leeren</Button>
        </template>
        <div ref="list" class="max-h-64 overflow-auto font-mono text-xs" data-test="console">
            <p v-if="log.messages.length === 0" class="p-3 text-muted-foreground">Noch keine Meldungen.</p>
            <table v-else class="w-full">
                <tbody>
                    <tr v-for="message in log.messages" :key="message.id" :class="statusClass[message.status]" data-test="console-row">
                        <td class="px-2 py-0.5 text-right text-muted-foreground">{{ message.id }}</td>
                        <td class="px-2 py-0.5 text-muted-foreground">{{ message.tag }}</td>
                        <td class="px-2 py-0.5">{{ message.text }}</td>
                        <td class="px-2 py-0.5 text-right">{{ message.value }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </FloatingPanel>
</template>
