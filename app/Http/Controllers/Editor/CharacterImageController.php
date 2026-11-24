<?php

namespace App\Http\Controllers\Editor;

use App\Enums\CharacterPose;
use App\Http\Controllers\Controller;
use App\Http\Requests\Editor\CharacterImageRequest;
use App\Models\BoardAsset;
use App\Models\Character;
use App\Models\CharacterImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CharacterImageController extends Controller
{
    /** Set the image of a pose; an existing image of that pose is replaced. */
    public function store(CharacterImageRequest $request, Character $character): RedirectResponse
    {
        Gate::authorize('manage', $character);

        $pose = CharacterPose::from($request->validated('pose'));
        $path = $request->file('image')->store("journeys/{$character->journey_id}/characters", BoardAsset::DISK);

        $character->images()->where('pose', $pose)->get()->each->delete();
        $character->images()->create(['pose' => $pose, 'path' => $path]);

        return back();
    }

    public function destroy(CharacterImage $image): RedirectResponse
    {
        Gate::authorize('manage', $image->character);

        $image->delete();

        return back();
    }
}
