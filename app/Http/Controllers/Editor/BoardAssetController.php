<?php

namespace App\Http\Controllers\Editor;

use App\Enums\AssetLayer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Editor\BoardAssetRequest;
use App\Models\Board;
use App\Models\BoardAsset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class BoardAssetController extends Controller
{
    public function store(BoardAssetRequest $request, Board $board): RedirectResponse
    {
        Gate::authorize('manage', $board);

        $board->assets()->create([
            'layer' => AssetLayer::from($request->validated('layer')),
            'path' => $request->file('image')->store("journeys/{$board->journey_id}", BoardAsset::DISK),
            'position' => ($board->assets()->max('position') ?? 0) + 1,
        ]);

        return back();
    }

    public function destroy(BoardAsset $asset): RedirectResponse
    {
        Gate::authorize('manage', $asset->board);

        $asset->delete();

        return back();
    }
}
