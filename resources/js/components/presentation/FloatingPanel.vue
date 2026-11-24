<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { useDraggable, useElementSize } from '@vueuse/core';
import { ChevronDown, ChevronUp, X } from 'lucide-vue-next';
import { onMounted, ref, watch, type Component } from 'vue';

export type PanelAnchor = 'top-left' | 'top-center' | 'top-right' | 'bottom-left' | 'bottom-center' | 'bottom-right';

// The panel last touched sits on top of the others.
let topZ = 50;

const props = withDefaults(defineProps<{ title: string; icon: Component; anchor?: PanelAnchor; width?: number }>(), {
    anchor: 'top-right',
    width: 320,
});

const emit = defineEmits<{ (e: 'close'): void }>();

const MARGIN = 24;
const TOP = 72; // below the status bar
const BOTTOM = 72; // above the navigation
const EDGE = 56; // keeps a grown panel clear of the navigation bar

const panel = ref<HTMLElement | null>(null);
const handle = ref<HTMLElement | null>(null);
const collapsed = ref(false);
const zIndex = ref(++topZ);

const bringToFront = () => {
    zIndex.value = ++topZ;
};

const { x, y, style } = useDraggable(panel, { handle, initialValue: { x: MARGIN, y: TOP } });

// A panel that grows (new console messages, longer notes) must not slide out of the window.
const { height } = useElementSize(panel);
watch(height, (value) => {
    if (value > 0 && y.value + value > window.innerHeight - EDGE) y.value = Math.max(window.innerHeight - value - EDGE, 0);
});

onMounted(() => {
    const el = panel.value;
    if (!el) return;

    const [vertical, horizontal] = props.anchor.split('-') as ['top' | 'bottom', 'left' | 'center' | 'right'];
    const left = { left: MARGIN, center: (window.innerWidth - el.offsetWidth) / 2, right: window.innerWidth - el.offsetWidth - MARGIN }[horizontal];
    const top = vertical === 'top' ? TOP : window.innerHeight - el.offsetHeight - BOTTOM;

    x.value = Math.max(left, 0);
    y.value = Math.max(top, 0);
});
</script>

<template>
    <section
        ref="panel"
        class="fixed flex max-h-[70vh] flex-col overflow-hidden rounded-lg border bg-card text-card-foreground shadow-lg"
        :style="[style, { width: `${width}px`, zIndex }]"
        :aria-label="title"
        data-test="panel"
        @pointerdown="bringToFront"
    >
        <header ref="handle" class="flex cursor-move select-none items-center gap-2 border-b bg-muted px-3 py-1.5" @dblclick="collapsed = !collapsed">
            <component :is="icon" class="size-4 text-muted-foreground" />
            <h2 class="flex-1 text-sm font-medium">{{ title }}</h2>
            <slot name="actions" />
            <Button variant="ghost" size="icon" class="size-6" :aria-label="collapsed ? 'Ausklappen' : 'Einklappen'" @click="collapsed = !collapsed">
                <component :is="collapsed ? ChevronDown : ChevronUp" class="size-3" />
            </Button>
            <Button variant="ghost" size="icon" class="size-6" aria-label="Schliessen" @click="emit('close')"><X class="size-3" /></Button>
        </header>
        <div v-show="!collapsed" class="min-h-0 flex-1 overflow-auto">
            <slot />
        </div>
    </section>
</template>
