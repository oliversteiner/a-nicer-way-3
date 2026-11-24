<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import type { EditorBackdrop } from '@/types/editor';
import { router, useForm } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';

const props = defineProps<{ backdrop: EditorBackdrop }>();

const layers: { value: EditorBackdrop['layer']; label: string }[] = [
    { value: 'back', label: 'Behind the boards' },
    { value: 'front', label: 'In front of the boards' },
];

const aligns: { value: EditorBackdrop['align']; label: string }[] = [
    { value: 'top', label: 'Top' },
    { value: 'center', label: 'Centre' },
    { value: 'bottom', label: 'Bottom' },
];

const form = useForm({
    layer: props.backdrop.layer,
    speed: props.backdrop.speed,
    align: props.backdrop.align,
    height: props.backdrop.height,
    repeat: props.backdrop.repeat,
});

const save = () => form.put(route('editor.backdrops.update', props.backdrop.id), { preserveScroll: true });
const remove = () => router.delete(route('editor.backdrops.destroy', props.backdrop.id), { preserveScroll: true });
</script>

<template>
    <li class="flex gap-4 p-4" data-test="backdrop">
        <img :src="backdrop.url" alt="" class="h-24 w-32 shrink-0 rounded-md border bg-muted object-contain" />

        <form class="grid flex-1 gap-4" @submit.prevent="save">
            <div class="grid gap-2" role="group" aria-label="Layer">
                <Label>Position</Label>
                <div class="flex flex-wrap gap-2">
                    <Button
                        v-for="item in layers"
                        :key="item.value"
                        type="button"
                        size="sm"
                        :variant="form.layer === item.value ? 'default' : 'secondary'"
                        :aria-pressed="form.layer === item.value"
                        @click="form.layer = item.value"
                    >
                        {{ item.label }}
                    </Button>
                </div>
                <InputError :message="form.errors.layer" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="grid gap-2">
                    <Label :for="`backdrop-speed-${backdrop.id}`">Speed</Label>
                    <Input :id="`backdrop-speed-${backdrop.id}`" v-model="form.speed" type="number" min="0.05" max="3" step="0.05" required />
                    <p class="text-xs text-muted-foreground">1 moves with the boards, less is slower.</p>
                    <InputError :message="form.errors.speed" />
                </div>
                <div class="grid gap-2">
                    <Label :for="`backdrop-height-${backdrop.id}`">Height (%)</Label>
                    <Input :id="`backdrop-height-${backdrop.id}`" v-model="form.height" type="number" min="10" max="100" step="5" required />
                    <InputError :message="form.errors.height" />
                </div>
            </div>

            <div class="grid gap-2" role="group" aria-label="Alignment">
                <Label>Alignment</Label>
                <div class="flex flex-wrap gap-2">
                    <Button
                        v-for="item in aligns"
                        :key="item.value"
                        type="button"
                        size="sm"
                        :variant="form.align === item.value ? 'default' : 'secondary'"
                        :aria-pressed="form.align === item.value"
                        @click="form.align = item.value"
                    >
                        {{ item.label }}
                    </Button>
                </div>
                <InputError :message="form.errors.align" />
            </div>

            <div class="grid gap-2">
                <Label :for="`backdrop-repeat-${backdrop.id}`" class="flex items-center space-x-3">
                    <Checkbox :id="`backdrop-repeat-${backdrop.id}`" v-model:checked="form.repeat" />
                    <span>Repeat along the whole journey</span>
                </Label>
                <InputError :message="form.errors.repeat" />
            </div>

            <div class="flex items-center gap-2">
                <Button type="submit" :disabled="form.processing || !form.isDirty">Save</Button>

                <Dialog>
                    <DialogTrigger as-child>
                        <Button type="button" variant="ghost" size="icon" aria-label="Delete backdrop"><Trash2 /></Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader class="space-y-3">
                            <DialogTitle>Delete this backdrop?</DialogTitle>
                            <DialogDescription>The image will be permanently deleted.</DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <DialogClose as-child><Button variant="secondary">Cancel</Button></DialogClose>
                            <DialogClose as-child><Button variant="destructive" @click="remove">Delete backdrop</Button></DialogClose>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </form>
    </li>
</template>
