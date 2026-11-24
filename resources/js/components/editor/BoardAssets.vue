<script setup lang="ts">
import ImageUpload from '@/components/editor/ImageUpload.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import type { EditorAsset, EditorBoard } from '@/types/editor';
import { router, useForm } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';

const props = defineProps<{ board: EditorBoard }>();

const layers: { name: EditorAsset['layer']; label: string; hint: string }[] = [
    { name: 'back', label: 'Back', hint: 'moves slowest' },
    { name: 'middle', label: 'Middle', hint: 'moves with the boards' },
    { name: 'front', label: 'Front', hint: 'moves fastest' },
];

const form = useForm<{ layer: EditorAsset['layer']; image: File | null }>({ layer: 'back', image: null });

const upload = (layer: EditorAsset['layer'], file: File) => {
    form.layer = layer;
    form.image = file;
    form.post(route('editor.assets.store', props.board.id), {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => form.reset('image'),
    });
};

const remove = (asset: EditorAsset) => router.delete(route('editor.assets.destroy', asset.id), { preserveScroll: true });

const assetsOf = (layer: EditorAsset['layer']) => props.board.assets.filter((asset) => asset.layer === layer);
</script>

<template>
    <section class="space-y-4" data-test="board-assets">
        <HeadingSmall title="Images" description="PNG, JPG, WebP or SVG up to 5 MB. Images are saved as soon as you upload them." />

        <div v-for="layer in layers" :key="layer.name" class="space-y-2" :data-layer="layer.name">
            <div class="flex items-center justify-between gap-2">
                <p class="text-sm font-medium">
                    {{ layer.label }} <span class="font-normal text-muted-foreground">({{ layer.hint }})</span>
                </p>
                <ImageUpload :disabled="form.processing" @select="(file) => upload(layer.name, file)" />
            </div>

            <ul v-if="assetsOf(layer.name).length" class="flex flex-wrap gap-2">
                <li v-for="asset in assetsOf(layer.name)" :key="asset.id" class="relative" data-test="asset">
                    <img :src="asset.url" alt="" class="h-16 w-24 rounded-md border bg-muted object-contain" />
                    <Button
                        type="button"
                        variant="destructive"
                        size="icon"
                        class="absolute -right-2 -top-2 size-6 rounded-full"
                        aria-label="Delete image"
                        @click="remove(asset)"
                    >
                        <Trash2 class="size-3" />
                    </Button>
                </li>
            </ul>
            <p v-else class="text-sm text-muted-foreground">No image.</p>
        </div>

        <InputError :message="form.errors.image ?? form.errors.layer" />
    </section>
</template>
