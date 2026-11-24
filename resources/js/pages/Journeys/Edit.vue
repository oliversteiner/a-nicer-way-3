<script setup lang="ts">
import BoardAssets from '@/components/editor/BoardAssets.vue';
import BoardForm from '@/components/editor/BoardForm.vue';
import CharacterEditor from '@/components/editor/CharacterEditor.vue';
import JourneyFields from '@/components/editor/JourneyFields.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import type { EditorBoard, EditorJourney } from '@/types/editor';
import { TransitionRoot } from '@headlessui/vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, ExternalLink, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{ journey: EditorJourney }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Journeys', href: '/editor/journeys' },
    { title: props.journey.title, href: `/editor/journeys/${props.journey.id}` },
];

const form = useForm({
    title: props.journey.title,
    description: props.journey.description ?? '',
    published: props.journey.published,
});

const save = () => form.put(route('editor.journeys.update', props.journey.id), { preserveScroll: true });

// Board dialog: `null` while closed, `'new'` for a new board, else the board being edited.
const editing = ref<number | 'new' | null>(null);
// Read the board from the props so uploads (which reload them) show up in the open dialog.
const editingBoard = computed(() =>
    typeof editing.value === 'number' ? (props.journey.boards.find((board) => board.id === editing.value) ?? null) : null,
);
const dialogOpen = (open: boolean) => {
    if (!open) editing.value = null;
};

const move = (board: EditorBoard, direction: 'up' | 'down') =>
    router.post(route('editor.boards.move', board.id), { direction }, { preserveScroll: true });

const removeBoard = (board: EditorBoard) => router.delete(route('editor.boards.destroy', board.id), { preserveScroll: true });

const deleteForm = useForm({});
const removeJourney = () => deleteForm.delete(route('editor.journeys.destroy', props.journey.id));

const summary = (board: EditorBoard) => [`Day ${board.day}`, board.time, board.location].filter(Boolean).join(' · ');
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="journey.title" />

        <div class="flex max-w-3xl flex-col space-y-10 p-4">
            <div class="flex flex-col space-y-6">
                <div class="flex items-start justify-between gap-4">
                    <HeadingSmall title="Journey" description="Title, description and visibility" />
                    <Button v-if="journey.published" variant="secondary" as-child>
                        <a :href="route('journeys.show', journey.slug)" target="_blank" rel="noopener"><ExternalLink />View</a>
                    </Button>
                </div>

                <form class="space-y-6" @submit.prevent="save">
                    <JourneyFields
                        v-model:title="form.title"
                        v-model:description="form.description"
                        v-model:published="form.published"
                        :errors="form.errors"
                    />

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Save</Button>

                        <TransitionRoot
                            :show="form.recentlySuccessful"
                            enter="transition ease-in-out"
                            enter-from="opacity-0"
                            leave="transition ease-in-out"
                            leave-to="opacity-0"
                        >
                            <p class="text-sm text-neutral-600">Saved.</p>
                        </TransitionRoot>
                    </div>
                </form>
            </div>

            <div class="flex flex-col space-y-6">
                <div class="flex items-start justify-between gap-4">
                    <HeadingSmall title="Boards" description="The stations of the story, in the order they are shown" />
                    <Button @click="editing = 'new'"><Plus />Add board</Button>
                </div>

                <p v-if="journey.boards.length === 0" class="text-sm text-muted-foreground" data-test="no-boards">This journey has no boards yet.</p>

                <ol v-else class="divide-y rounded-lg border">
                    <li v-for="(board, index) in journey.boards" :key="board.id" class="flex items-center gap-3 p-3" data-test="board">
                        <span class="w-6 text-right text-sm text-muted-foreground">{{ board.position }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">{{ board.title || 'Untitled' }}</p>
                            <p class="truncate text-sm text-muted-foreground">{{ summary(board) }}</p>
                        </div>
                        <Button variant="ghost" size="icon" aria-label="Move up" :disabled="index === 0" @click="move(board, 'up')"
                            ><ArrowUp
                        /></Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label="Move down"
                            :disabled="index === journey.boards.length - 1"
                            @click="move(board, 'down')"
                        >
                            <ArrowDown />
                        </Button>
                        <Button variant="ghost" size="icon" aria-label="Edit board" @click="editing = board.id"><Pencil /></Button>
                        <Dialog>
                            <DialogTrigger as-child>
                                <Button variant="ghost" size="icon" aria-label="Delete board"><Trash2 /></Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader class="space-y-3">
                                    <DialogTitle>Delete this board?</DialogTitle>
                                    <DialogDescription>
                                        Board {{ board.position }} ({{ board.title || 'Untitled' }}) will be deleted. The following boards move up.
                                    </DialogDescription>
                                </DialogHeader>
                                <DialogFooter>
                                    <DialogClose as-child><Button variant="secondary">Cancel</Button></DialogClose>
                                    <DialogClose as-child
                                        ><Button variant="destructive" @click="removeBoard(board)">Delete board</Button></DialogClose
                                    >
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </li>
                </ol>
            </div>

            <CharacterEditor :journey-id="journey.id" :characters="journey.characters" />

            <div class="space-y-6">
                <HeadingSmall title="Delete journey" description="Delete this journey and all of its boards" />
                <div class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
                    <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                        <p class="font-medium">Warning</p>
                        <p class="text-sm">Please proceed with caution, this cannot be undone.</p>
                    </div>
                    <Dialog>
                        <DialogTrigger as-child>
                            <Button variant="destructive">Delete journey</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader class="space-y-3">
                                <DialogTitle>Delete “{{ journey.title }}”?</DialogTitle>
                                <DialogDescription
                                    >The journey and all {{ journey.boards.length }} boards will be permanently deleted.</DialogDescription
                                >
                            </DialogHeader>
                            <DialogFooter>
                                <DialogClose as-child><Button variant="secondary">Cancel</Button></DialogClose>
                                <Button variant="destructive" :disabled="deleteForm.processing" @click="removeJourney">Delete journey</Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>
        </div>

        <Dialog :open="editing !== null" @update:open="dialogOpen">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>{{ editing === 'new' ? 'Add board' : 'Edit board' }}</DialogTitle>
                    <DialogDescription>Everything except the day is optional.</DialogDescription>
                </DialogHeader>
                <BoardForm
                    v-if="editing === 'new' || editingBoard"
                    :key="editing ?? 'none'"
                    :journey-id="journey.id"
                    :board="editingBoard"
                    @saved="editing = null"
                />
                <BoardAssets v-if="editingBoard" :board="editingBoard" />
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
