<script setup lang="ts">
import BackdropCard from '@/components/editor/BackdropCard.vue';
import ImageUpload from '@/components/editor/ImageUpload.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import type { EditorBackdrop } from '@/types/editor';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{ journeyId: number; backdrops: EditorBackdrop[] }>();

const form = useForm<{ layer: EditorBackdrop['layer']; image: File | null }>({ layer: 'back', image: null });

const upload = (layer: EditorBackdrop['layer'], file: File) => {
    form.layer = layer;
    form.image = file;
    form.post(route('editor.backdrops.store', props.journeyId), {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => form.reset('image'),
    });
};
</script>

<template>
    <div class="flex flex-col space-y-6" data-test="backdrops">
        <HeadingSmall
            title="Backdrops"
            description="Images that run along the whole journey behind or in front of the boards, such as sky or mountains. PNG, JPG, WebP or SVG up to 5 MB."
        />

        <div class="flex flex-wrap items-center gap-2">
            <ImageUpload label="Add behind the boards" :disabled="form.processing" @select="(file) => upload('back', file)" />
            <ImageUpload label="Add in front of the boards" :disabled="form.processing" @select="(file) => upload('front', file)" />
        </div>
        <InputError :message="form.errors.image ?? form.errors.layer" />

        <p v-if="backdrops.length === 0" class="text-sm text-muted-foreground" data-test="no-backdrops">This journey has no backdrops yet.</p>

        <ul v-else class="divide-y rounded-lg border">
            <BackdropCard v-for="backdrop in backdrops" :key="backdrop.id" :backdrop="backdrop" />
        </ul>
    </div>
</template>
