<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Editor\CharacterRequest;
use App\Models\Character;
use App\Models\Journey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CharacterController extends Controller
{
    public function store(CharacterRequest $request, Journey $journey): RedirectResponse
    {
        Gate::authorize('manage', $journey);

        $journey->characters()->create([
            'name' => $request->validated('name'),
            'position' => ($journey->characters()->max('position') ?? 0) + 1,
        ]);

        return back();
    }

    public function update(CharacterRequest $request, Character $character): RedirectResponse
    {
        Gate::authorize('manage', $character);

        $character->update(['name' => $request->validated('name')]);

        return back();
    }

    public function destroy(Character $character): RedirectResponse
    {
        Gate::authorize('manage', $character);

        $character->delete();

        return back();
    }
}
