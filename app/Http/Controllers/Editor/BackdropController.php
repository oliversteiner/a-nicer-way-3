<?php

namespace App\Http\Controllers\Editor;

use App\Enums\AssetLayer;
use App\Enums\BackdropAlign;
use App\Http\Controllers\Controller;
use App\Http\Requests\Editor\BackdropRequest;
use App\Http\Requests\Editor\BackdropUploadRequest;
use App\Models\BoardAsset;
use App\Models\Journey;
use App\Models\JourneyBackdrop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class BackdropController extends Controller
{
    /** Add a backdrop with sensible defaults for its layer; the settings are adjusted afterwards. */
    public function store(BackdropUploadRequest $request, Journey $journey): RedirectResponse
    {
        Gate::authorize('manage', $journey);

        $layer = AssetLayer::from($request->validated('layer'));

        $journey->backdrops()->create([
            'layer' => $layer,
            'path' => $request->file('image')->store("journeys/{$journey->id}/backdrops", BoardAsset::DISK),
            'position' => ($journey->backdrops()->max('position') ?? 0) + 1,
            'speed' => $layer === AssetLayer::Back ? 0.3 : 1.5,
            'align' => BackdropAlign::Bottom,
            'height' => 100,
            'repeat' => $layer === AssetLayer::Back,
        ]);

        return back();
    }

    public function update(BackdropRequest $request, JourneyBackdrop $backdrop): RedirectResponse
    {
        Gate::authorize('manage', $backdrop);

        $backdrop->update($request->validated());

        return back();
    }

    public function destroy(JourneyBackdrop $backdrop): RedirectResponse
    {
        Gate::authorize('manage', $backdrop);

        $backdrop->delete();

        return back();
    }
}
