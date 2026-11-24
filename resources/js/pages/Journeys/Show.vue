<script setup lang="ts">
import BoardNav from '@/components/presentation/BoardNav.vue';
import BoardScroller from '@/components/presentation/BoardScroller.vue';
import SimulatorPanel from '@/components/presentation/SimulatorPanel.vue';
import StatusBar from '@/components/presentation/StatusBar.vue';
import PresentationLayout from '@/layouts/PresentationLayout.vue';
import { usePresentationStore } from '@/stores/presentation';
import type { Journey } from '@/types/presentation';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{ journey: Journey }>();

const store = usePresentationStore();
store.setBoardCount(props.journey.boards.length);

const currentBoard = computed(() => props.journey.boards[store.currentIndex]);
</script>

<template>
    <Head :title="journey.title" />
    <PresentationLayout>
        <StatusBar :title="journey.title" :board="currentBoard" />
        <BoardScroller :boards="journey.boards" />
        <BoardNav />
        <SimulatorPanel :board="currentBoard" />
    </PresentationLayout>
</template>
