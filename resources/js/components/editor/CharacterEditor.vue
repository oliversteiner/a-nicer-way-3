<script setup lang="ts">
import CharacterCard from '@/components/editor/CharacterCard.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { EditorCharacter } from '@/types/editor';
import { useForm } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';

const props = defineProps<{ journeyId: number; characters: EditorCharacter[] }>();

const form = useForm({ name: '' });
const add = () => form.post(route('editor.characters.store', props.journeyId), { preserveScroll: true, onSuccess: () => form.reset('name') });
</script>

<template>
    <div class="flex flex-col space-y-6" data-test="characters">
        <HeadingSmall
            title="Characters"
            description="Figures that stand in the scene. Each pose can have its own image; a missing pose shows the standing image."
        />

        <form class="grid gap-2" @submit.prevent="add">
            <Label for="character-new">New character</Label>
            <div class="flex gap-2">
                <Input id="character-new" v-model="form.name" placeholder="Name" required />
                <Button :disabled="form.processing"><Plus />Add character</Button>
            </div>
            <InputError :message="form.errors.name" />
        </form>

        <p v-if="characters.length === 0" class="text-sm text-muted-foreground" data-test="no-characters">This journey has no characters yet.</p>

        <ul v-else class="divide-y rounded-lg border">
            <CharacterCard v-for="character in characters" :key="character.id" :character="character" />
        </ul>
    </div>
</template>
