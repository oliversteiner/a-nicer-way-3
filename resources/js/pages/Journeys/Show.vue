<script setup lang="ts">
import BoardListPanel from '@/components/presentation/BoardListPanel.vue';
import BoardNav from '@/components/presentation/BoardNav.vue';
import BoardScroller from '@/components/presentation/BoardScroller.vue';
import CharacterPanel from '@/components/presentation/CharacterPanel.vue';
import ConsolePanel from '@/components/presentation/ConsolePanel.vue';
import NotesPanel from '@/components/presentation/NotesPanel.vue';
import RemotePanel from '@/components/presentation/RemotePanel.vue';
import SimulatorPanel from '@/components/presentation/SimulatorPanel.vue';
import StatusBar from '@/components/presentation/StatusBar.vue';
import TimerPanel from '@/components/presentation/TimerPanel.vue';
import PresentationLayout from '@/layouts/PresentationLayout.vue';
import { usePresentationStore } from '@/stores/presentation';
import { useRemoteStore } from '@/stores/remote';
import type { Journey } from '@/types/presentation';
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, watch } from 'vue';

const props = defineProps<{ journey: Journey }>();

const store = usePresentationStore();
store.setBoardCount(props.journey.boards.length);
store.setCharacters(props.journey.characters.map((character) => character.id));

const currentBoard = computed(() => props.journey.boards[store.currentIndex]);

// Remote control: tell it what the presentation shows whenever that changes.
const remote = useRemoteStore();
remote.setStateSource(() => {
    const board = currentBoard.value;

    return {
        index: store.currentIndex,
        count: props.journey.boards.length,
        board: board
            ? { position: board.position, title: board.title, notes: board.notes, day: board.day, time: board.time, location: board.location }
            : null,
        characters: props.journey.characters.map((character) => ({ id: character.id, name: character.name })),
        character_id: store.characterId,
        character_pose: store.characterPose,
        timer: {
            remaining: store.timerRemaining,
            total: store.timerTotal,
            running: store.timerRunning,
            nearEnd: store.timerNearEnd,
            timeOut: store.timerTimeOut,
        },
    };
});

watch(
    // While the timer runs the remote counts down itself; only changes are sent.
    () => [
        store.currentIndex,
        store.timerRunning,
        store.timerTotal,
        store.timerRunning ? null : store.timerRemaining,
        store.timerTimeOut,
        store.characterId,
        store.characterPose,
    ],
    () => remote.publish(),
);

onMounted(() => remote.resume(props.journey.slug));
</script>

<template>
    <Head :title="journey.title" />
    <PresentationLayout>
        <StatusBar :title="journey.title" :board="currentBoard" />
        <BoardScroller :boards="journey.boards" :characters="journey.characters" />
        <BoardNav />
        <SimulatorPanel :board="currentBoard" />
        <BoardListPanel v-if="store.panels.boards" :boards="journey.boards" />
        <NotesPanel v-if="store.panels.notes" :board="currentBoard" />
        <ConsolePanel v-if="store.panels.console" />
        <TimerPanel v-if="store.panels.timer" />
        <CharacterPanel v-if="store.panels.character" :characters="journey.characters" />
        <RemotePanel v-if="store.panels.remote" :slug="journey.slug" />
    </PresentationLayout>
</template>
