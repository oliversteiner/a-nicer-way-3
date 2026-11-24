<script setup lang="ts">
import ImageUpload from '@/components/editor/ImageUpload.vue';
import InputError from '@/components/InputError.vue';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { EditorCharacter, EditorCharacterImage } from '@/types/editor';
import { router, useForm } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';

const props = defineProps<{ character: EditorCharacter }>();

type Pose = EditorCharacterImage['pose'];

const poses: { pose: Pose; label: string }[] = [
    { pose: 'idle', label: 'Standing' },
    { pose: 'go', label: 'Walking' },
    { pose: 'pause', label: 'Break' },
    { pose: 'greet', label: 'Greeting' },
];

const nameForm = useForm({ name: props.character.name });
const saveName = () => nameForm.put(route('editor.characters.update', props.character.id), { preserveScroll: true });

const uploadForm = useForm<{ pose: Pose; image: File | null }>({ pose: 'idle', image: null });
const upload = (pose: Pose, file: File) => {
    uploadForm.pose = pose;
    uploadForm.image = file;
    uploadForm.post(route('editor.character-images.store', props.character.id), {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => uploadForm.reset('image'),
    });
};

const imageOf = (pose: Pose) => props.character.images.find((image) => image.pose === pose);
const removeImage = (image: EditorCharacterImage) => router.delete(route('editor.character-images.destroy', image.id), { preserveScroll: true });
const removeCharacter = () => router.delete(route('editor.characters.destroy', props.character.id), { preserveScroll: true });
</script>

<template>
    <li class="space-y-4 p-4" data-test="character">
        <div class="flex items-end gap-2">
            <form class="grid flex-1 gap-2" @submit.prevent="saveName">
                <Label :for="`character-name-${character.id}`">Name</Label>
                <div class="flex gap-2">
                    <Input :id="`character-name-${character.id}`" v-model="nameForm.name" required />
                    <Button type="submit" variant="secondary" :disabled="nameForm.processing || nameForm.name === character.name">Rename</Button>
                </div>
                <InputError :message="nameForm.errors.name" />
            </form>

            <Dialog>
                <DialogTrigger as-child>
                    <Button variant="ghost" size="icon" :aria-label="`Delete ${character.name}`"><Trash2 /></Button>
                </DialogTrigger>
                <DialogContent>
                    <DialogHeader class="space-y-3">
                        <DialogTitle>Delete “{{ character.name }}”?</DialogTitle>
                        <DialogDescription>The character and its images will be permanently deleted.</DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <DialogClose as-child><Button variant="secondary">Cancel</Button></DialogClose>
                        <DialogClose as-child><Button variant="destructive" @click="removeCharacter">Delete character</Button></DialogClose>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div v-for="item in poses" :key="item.pose" class="space-y-2" :data-pose="item.pose">
                <p class="text-sm font-medium">{{ item.label }}</p>
                <div class="relative">
                    <img
                        v-if="imageOf(item.pose)"
                        :src="imageOf(item.pose)!.url"
                        alt=""
                        class="h-20 w-full rounded-md border bg-muted object-contain"
                    />
                    <p v-else class="flex h-20 items-center justify-center rounded-md border border-dashed text-xs text-muted-foreground">No image</p>
                    <Button
                        v-if="imageOf(item.pose)"
                        type="button"
                        variant="destructive"
                        size="icon"
                        class="absolute -right-2 -top-2 size-6 rounded-full"
                        aria-label="Delete image"
                        @click="removeImage(imageOf(item.pose)!)"
                    >
                        <Trash2 class="size-3" />
                    </Button>
                </div>
                <ImageUpload
                    :disabled="uploadForm.processing"
                    :label="imageOf(item.pose) ? 'Replace' : 'Upload'"
                    @select="(file) => upload(item.pose, file)"
                />
            </div>
        </div>
        <InputError :message="uploadForm.errors.image ?? uploadForm.errors.pose" />
    </li>
</template>
