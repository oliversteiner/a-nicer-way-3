<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Editor\JourneyRequest;
use App\Models\Journey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class JourneyController extends Controller
{
    public function index(Request $request): Response
    {
        $journeys = $request->user()->journeys()->withCount('boards')->latest()->get();

        return Inertia::render('Journeys/Index', [
            'journeys' => $journeys->map(fn (Journey $journey) => [
                'id' => $journey->id,
                'title' => $journey->title,
                'slug' => $journey->slug,
                'published' => $journey->published_at !== null,
                'boards_count' => $journey->boards_count,
            ])->values(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Journeys/Create');
    }

    public function store(JourneyRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $journey = $request->user()->journeys()->create([
            'title' => $data['title'],
            'slug' => $this->uniqueSlug($data['title']),
            'description' => $data['description'] ?? null,
            'published_at' => $data['published'] ? now() : null,
        ]);

        return to_route('editor.journeys.edit', $journey);
    }

    public function edit(Journey $journey): Response
    {
        Gate::authorize('manage', $journey);

        $journey->load('boards');

        return Inertia::render('Journeys/Edit', [
            'journey' => [
                'id' => $journey->id,
                'title' => $journey->title,
                'slug' => $journey->slug,
                'description' => $journey->description,
                'published' => $journey->published_at !== null,
                'boards' => $journey->boards->map(fn ($board) => [
                    'id' => $board->id,
                    'position' => $board->position,
                    'title' => $board->title,
                    'description' => $board->description,
                    'notes' => $board->notes,
                    'day' => $board->day,
                    'time' => $board->time ? substr($board->time, 0, 5) : null,
                    'location' => $board->location,
                    'is_night' => $board->is_night,
                    'mood' => $board->mood,
                    'simulator_screen' => $board->simulator_state['screen'] ?? null,
                ])->values(),
            ],
        ]);
    }

    public function update(JourneyRequest $request, Journey $journey): RedirectResponse
    {
        Gate::authorize('manage', $journey);

        $data = $request->validated();

        $journey->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            // Keep the original publication date while it stays published.
            'published_at' => $data['published'] ? ($journey->published_at ?? now()) : null,
        ]);

        return back();
    }

    public function destroy(Journey $journey): RedirectResponse
    {
        Gate::authorize('manage', $journey);

        $journey->delete();

        return to_route('editor.journeys.index');
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'journey';
        $slug = $base;

        for ($i = 2; Journey::query()->where('slug', $slug)->exists(); $i++) {
            $slug = "$base-$i";
        }

        return $slug;
    }
}
