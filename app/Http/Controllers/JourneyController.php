<?php

namespace App\Http\Controllers;

use App\Models\Journey;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JourneyController extends Controller
{
    public function show(Request $request, Journey $journey): Response
    {
        $isOwner = $request->user()?->id === $journey->user_id;

        abort_unless($isOwner || ($journey->published_at?->isPast() ?? false), 404);

        $journey->load('boards.assets');

        return Inertia::render('Journeys/Show', [
            'journey' => [
                'title' => $journey->title,
                'slug' => $journey->slug,
                'description' => $journey->description,
                'boards' => $journey->boards->map(fn ($board) => [
                    'id' => $board->id,
                    'position' => $board->position,
                    'title' => $board->title,
                    'description' => $board->description,
                    'notes' => $board->notes,
                    'day' => $board->day,
                    'time' => $board->time,
                    'location' => $board->location,
                    'is_night' => $board->is_night,
                    'mood' => $board->mood,
                    'simulator_state' => $board->simulator_state,
                    'assets' => $board->assets->map(fn ($asset) => [
                        'layer' => $asset->layer->value,
                        'url' => route('assets.show', $asset),
                    ])->values(),
                ])->values(),
            ],
        ]);
    }
}
