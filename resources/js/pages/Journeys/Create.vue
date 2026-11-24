<script setup lang="ts">
import JourneyFields from '@/components/editor/JourneyFields.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Journeys', href: '/editor/journeys' },
    { title: 'New journey', href: '/editor/journeys/create' },
];

const form = useForm({ title: '', description: '', published: false });

const submit = () => form.post(route('editor.journeys.store'));
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="New journey" />

        <div class="flex max-w-2xl flex-col space-y-6 p-4">
            <HeadingSmall title="New journey" description="You add the boards on the next page" />

            <form class="space-y-6" @submit.prevent="submit">
                <JourneyFields
                    v-model:title="form.title"
                    v-model:description="form.description"
                    v-model:published="form.published"
                    :errors="form.errors"
                />

                <div class="flex items-center gap-4">
                    <Button :disabled="form.processing">Create</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
