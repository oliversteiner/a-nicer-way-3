<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

defineProps<{ errors: Partial<Record<'title' | 'description' | 'published', string>> }>();

const title = defineModel<string>('title', { required: true });
const description = defineModel<string>('description', { required: true });
const published = defineModel<boolean>('published', { required: true });
</script>

<template>
    <div class="grid gap-2">
        <Label for="title">Title</Label>
        <Input id="title" v-model="title" class="mt-1 block w-full" required placeholder="Title" />
        <InputError class="mt-2" :message="errors.title" />
    </div>

    <div class="grid gap-2">
        <Label for="description">Description</Label>
        <Textarea id="description" v-model="description" class="mt-1 block w-full" placeholder="What is this journey about?" />
        <InputError class="mt-2" :message="errors.description" />
    </div>

    <div class="grid gap-2">
        <Label for="published" class="flex items-center space-x-3">
            <Checkbox id="published" v-model:checked="published" />
            <span>Published (visible to everyone with the link)</span>
        </Label>
        <InputError class="mt-2" :message="errors.published" />
    </div>
</template>
