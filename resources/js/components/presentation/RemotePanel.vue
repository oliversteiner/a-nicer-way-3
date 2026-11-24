<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { usePresentationStore } from '@/stores/presentation';
import { useRemoteStore } from '@/stores/remote';
import { useClipboard } from '@vueuse/core';
import { Cast, Copy, RefreshCw } from 'lucide-vue-next';
import QRCode from 'qrcode';
import { onMounted, ref, watch } from 'vue';
import FloatingPanel from './FloatingPanel.vue';

const props = defineProps<{ slug: string }>();

const store = usePresentationStore();
const remote = useRemoteStore();
const { copy, copied } = useClipboard();
const qr = ref('');

onMounted(() => {
    if (remote.status === 'idle' || remote.status === 'error') remote.pair(props.slug);
});

// The QR code is a generated SVG of our own URL.
watch(
    () => remote.pairing?.remote_url,
    async (url) => {
        qr.value = url ? await QRCode.toString(url, { type: 'svg', margin: 1, width: 192 }) : '';
    },
    { immediate: true },
);
</script>

<template>
    <FloatingPanel title="Fernsteuerung" :icon="Cast" anchor="bottom-center" :width="260" @close="store.closePanel('remote')">
        <div class="space-y-3 p-4 text-center text-sm" data-test="remote-panel">
            <p v-if="remote.status === 'pairing'" class="text-muted-foreground" role="status">Verbinde …</p>

            <template v-else-if="remote.status === 'error'">
                <p class="text-destructive" role="alert">Die Fernsteuerung konnte nicht gestartet werden.</p>
                <Button variant="secondary" @click="remote.pair(slug)"><RefreshCw />Erneut versuchen</Button>
            </template>

            <template v-else-if="remote.pairing">
                <div class="mx-auto size-48 overflow-hidden rounded-md bg-white" aria-label="QR-Code zur Fernsteuerung" role="img" v-html="qr" />
                <p class="break-all text-xs text-muted-foreground" data-test="remote-url">{{ remote.pairing.remote_url }}</p>
                <div class="flex items-center justify-center gap-2">
                    <Button variant="secondary" size="sm" @click="copy(remote.pairing.remote_url)"
                        ><Copy />{{ copied ? 'Kopiert' : 'Link kopieren' }}</Button
                    >
                    <Button variant="ghost" size="sm" @click="remote.pair(slug)"><RefreshCw />Neu koppeln</Button>
                </div>
                <p :class="remote.connected ? 'text-green-600' : 'text-muted-foreground'" role="status" data-test="remote-status">
                    {{ remote.connected ? 'Fernsteuerung verbunden' : 'Wartet auf die Fernsteuerung …' }}
                </p>
            </template>
        </div>
    </FloatingPanel>
</template>
