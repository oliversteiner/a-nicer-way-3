<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { DialogClose, DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { EditorBoard } from '@/types/editor';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{ journeyId: number; board: EditorBoard | null }>();
const emit = defineEmits<{ (e: 'saved'): void }>();

const form = useForm({
    title: props.board?.title ?? '',
    description: props.board?.description ?? '',
    notes: props.board?.notes ?? '',
    day: props.board?.day ?? 1,
    time: props.board?.time ?? '',
    location: props.board?.location ?? '',
    is_night: props.board?.is_night ?? false,
    mood: props.board?.mood ?? '',
    simulator_screen: props.board?.simulator_screen ?? '',
});

const submit = () => {
    const options = { preserveScroll: true, onSuccess: () => emit('saved') };

    if (props.board) {
        form.put(route('editor.boards.update', props.board.id), options);
    } else {
        form.post(route('editor.boards.store', props.journeyId), options);
    }
};
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <div class="grid gap-2">
            <Label for="board-title">Title</Label>
            <Input id="board-title" v-model="form.title" placeholder="Title" />
            <InputError :message="form.errors.title" />
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div class="grid gap-2">
                <Label for="board-day">Day</Label>
                <Input id="board-day" v-model="form.day" type="number" min="1" max="31" required />
                <InputError :message="form.errors.day" />
            </div>
            <div class="grid gap-2">
                <Label for="board-time">Time</Label>
                <Input id="board-time" v-model="form.time" type="time" />
                <InputError :message="form.errors.time" />
            </div>
            <div class="grid content-end gap-2 pb-2">
                <Label for="board-night" class="flex items-center space-x-3">
                    <Checkbox id="board-night" v-model:checked="form.is_night" />
                    <span>Night</span>
                </Label>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
                <Label for="board-location">Location</Label>
                <Input id="board-location" v-model="form.location" placeholder="Location" />
                <InputError :message="form.errors.location" />
            </div>
            <div class="grid gap-2">
                <Label for="board-mood">Mood</Label>
                <Input id="board-mood" v-model="form.mood" placeholder="Mood" />
                <InputError :message="form.errors.mood" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="board-screen">Phone screen</Label>
            <Input id="board-screen" v-model="form.simulator_screen" placeholder="What the phone shows" />
            <InputError :message="form.errors.simulator_screen" />
        </div>

        <div class="grid gap-2">
            <Label for="board-description">Description</Label>
            <Textarea id="board-description" v-model="form.description" />
            <InputError :message="form.errors.description" />
        </div>

        <div class="grid gap-2">
            <Label for="board-notes">Notes</Label>
            <Textarea id="board-notes" v-model="form.notes" />
            <InputError :message="form.errors.notes" />
        </div>

        <DialogFooter>
            <DialogClose as-child>
                <Button type="button" variant="secondary">Cancel</Button>
            </DialogClose>
            <Button type="submit" :disabled="form.processing">Save</Button>
        </DialogFooter>
    </form>
</template>
