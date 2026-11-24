<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import type { JourneySummary } from '@/types/editor';
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';

defineProps<{ journeys: JourneySummary[] }>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Journeys', href: '/editor/journeys' }];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Journeys" />

        <div class="flex flex-col space-y-6 p-4">
            <div class="flex items-start justify-between gap-4">
                <HeadingSmall title="Journeys" description="Your presentations and their boards" />
                <Button as-child>
                    <Link :href="route('editor.journeys.create')"><Plus />New journey</Link>
                </Button>
            </div>

            <p v-if="journeys.length === 0" class="text-sm text-muted-foreground" data-test="empty">You have no journeys yet.</p>

            <ul v-else class="divide-y rounded-lg border">
                <li v-for="journey in journeys" :key="journey.id" class="flex items-center justify-between gap-4 p-4" data-test="journey">
                    <div>
                        <Link :href="route('editor.journeys.edit', journey.id)" class="font-medium hover:underline">{{ journey.title }}</Link>
                        <p class="text-sm text-muted-foreground">
                            {{ journey.boards_count }} {{ journey.boards_count === 1 ? 'board' : 'boards' }} ·
                            {{ journey.published ? 'Published' : 'Draft' }}
                        </p>
                    </div>
                    <Button variant="secondary" as-child>
                        <Link :href="route('editor.journeys.edit', journey.id)">Edit</Link>
                    </Button>
                </li>
            </ul>
        </div>
    </AppLayout>
</template>
