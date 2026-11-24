<?php

namespace App\Support;

use App\Models\Journey;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * A pairing between a presentation and its remote control.
 *
 * The remote token is shared (QR code / link) and names the broadcast channels.
 * The presenter token never leaves the presenter's browser; only it may publish
 * the presentation state or end the session. Only a hash of it is stored.
 */
class RemoteSession
{
    public const TTL_HOURS = 12;

    /** @return array{remote_token: string, presenter_token: string} */
    public static function create(Journey $journey): array
    {
        $remote = Str::random(24);
        $presenter = Str::random(40);

        Cache::put(self::key($remote), [
            'journey_id' => $journey->id,
            'presenter_hash' => hash('sha256', $presenter),
        ], now()->addHours(self::TTL_HOURS));

        return ['remote_token' => $remote, 'presenter_token' => $presenter];
    }

    /** @return array{journey_id: int, presenter_hash: string}|null */
    public static function find(string $remoteToken): ?array
    {
        return Cache::get(self::key($remoteToken));
    }

    public static function isPresenter(string $remoteToken, ?string $presenterToken): bool
    {
        $session = self::find($remoteToken);

        return $session !== null
            && $presenterToken !== null
            && hash_equals($session['presenter_hash'], hash('sha256', $presenterToken));
    }

    public static function forget(string $remoteToken): void
    {
        Cache::forget(self::key($remoteToken));
    }

    private static function key(string $remoteToken): string
    {
        return 'remote-session:'.$remoteToken;
    }
}
