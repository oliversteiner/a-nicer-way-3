<?php

namespace App\Http\Controllers;

use App\Models\BoardAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetController extends Controller
{
    /**
     * Serve an uploaded image of a published journey (or of the owner's draft).
     *
     * Uploaded SVGs can carry scripts, so every file is sent with a sandboxing
     * policy and without MIME sniffing: opening the URL directly runs nothing.
     */
    public function show(Request $request, BoardAsset $asset): StreamedResponse
    {
        $journey = $asset->board->journey;
        $isOwner = $request->user()?->id === $journey->user_id;

        abort_unless($isOwner || ($journey->published_at?->isPast() ?? false), 404);

        $disk = Storage::disk(BoardAsset::DISK);
        abort_unless($disk->exists($asset->path), 404);

        return $disk->response($asset->path, null, [
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => $isOwner ? 'private, max-age=0, must-revalidate' : 'public, max-age=3600',
        ]);
    }
}
