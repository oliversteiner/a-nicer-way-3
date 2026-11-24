<?php

namespace App\Services;

use App\Enums\AssetLayer;
use App\Enums\BackdropAlign;
use App\Enums\CharacterPose;
use App\Models\BoardAsset;
use App\Models\Journey;
use App\Models\User;
use Illuminate\Http\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Throwable;

/**
 * Imports the journey of the old v2 website (the `web` folder with data/defaultdata.json and images/).
 *
 * Boards come from the time way points; their images are copied to the private disk. The old
 * website kept the figures and the sky/mountain background in its CSS, so they are mapped here.
 */
class LegacyJourneyImporter
{
    /** Figures of the old website: pose => image file below images/. */
    private const CHARACTERS = [
        'Kevin' => [
            'idle' => 'character/character-kevin-warten.png',
            'go' => 'character/character-kevin-laufen.png',
            'pause' => 'character/character-kevin-mobile.png',
            'greet' => 'character/character-kevin-winken.png',
        ],
        'Gruppe' => ['idle' => 'character/character-gruppe.png'],
        'Bus' => ['idle' => 'character/character-bus.png', 'go' => 'character/character-bus-mit-kevin.png'],
    ];

    /** Journey-wide backdrops of the old website: [file, layer, speed, align, height in %, repeat]. */
    private const BACKDROPS = [
        ['papier/papierhintergrund.jpg', 'back', 0.2, 'bottom', 100, true],
        ['himmel/ANW_Berge.png', 'back', 0.4, 'bottom', 60, true],
    ];

    /** @var list<string> Paths copied so far, removed again if the import fails. */
    private array $copied = [];

    /** @var list<string> */
    private array $missing = [];

    /**
     * @return array{journey: Journey, boards: int, assets: int, characters: int, images: int, backdrops: int, missing: list<string>}
     */
    public function import(string $source, User $owner, string $slug, string $title, bool $publish, bool $replace): array
    {
        $waypoints = $this->waypoints($source);
        $this->copied = [];
        $this->missing = [];

        $existing = Journey::query()->where('slug', $slug)->first();
        if ($existing) {
            if (! $replace) {
                throw new InvalidArgumentException("A journey with the slug \"{$slug}\" already exists. Use --replace to import it again.");
            }
            if ($existing->user_id !== $owner->id) {
                throw new InvalidArgumentException("The journey \"{$slug}\" belongs to another user and is not replaced.");
            }
        }

        try {
            return DB::transaction(function () use ($source, $owner, $slug, $title, $publish, $existing, $waypoints) {
                $existing?->delete();

                $journey = Journey::query()->create([
                    'user_id' => $owner->id,
                    'title' => $title,
                    'slug' => $slug,
                    'description' => 'Imported from the earlier website (v2).',
                    'published_at' => $publish ? now() : null,
                ]);

                $stats = ['boards' => 0, 'assets' => 0, 'characters' => 0, 'images' => 0, 'backdrops' => 0];

                foreach ($waypoints as $index => $point) {
                    $this->importBoard($source, $journey, $index + 1, $point, $stats);
                }
                $this->importCharacters($source, $journey, $stats);
                $this->importBackdrops($source, $journey, $stats);

                return $stats + ['journey' => $journey, 'missing' => $this->missing];
            });
        } catch (Throwable $e) {
            // The database rolled back; take the copied files with it.
            Storage::disk(BoardAsset::DISK)->delete($this->copied);

            throw $e;
        }
    }

    /** @return list<array<string, mixed>> */
    private function waypoints(string $source): array
    {
        $file = rtrim($source, '/').'/data/defaultdata.json';
        if (! is_file($file)) {
            throw new InvalidArgumentException("Not an old website folder: {$file} not found.");
        }

        $data = json_decode((string) file_get_contents($file), true);
        if (! is_array($data) || ! isset($data['data']) || ! is_array($data['data'])) {
            throw new InvalidArgumentException("{$file} is not valid: no \"data\" list.");
        }

        $points = array_values(array_filter($data['data'], fn ($item) => is_array($item) && ($item['type'] ?? null) === 'TimeWayPoint'));
        if ($points === []) {
            throw new InvalidArgumentException("{$file} contains no time way points.");
        }

        usort($points, fn ($a, $b) => ($a['sequence'] ?? 0) <=> ($b['sequence'] ?? 0));

        return $points;
    }

    /**
     * @param  array<string, mixed>  $point
     * @param  array<string, int>  $stats
     */
    private function importBoard(string $source, Journey $journey, int $position, array $point, array &$stats): void
    {
        $place = trim((string) ($point['place'] ?? ''));
        $message = trim((string) ($point['message'] ?? ''));
        $scene = trim((string) ($point['hype']['scene']['sceneName'] ?? ''));

        $board = $journey->boards()->create([
            'position' => $position,
            'title' => $place !== '' ? $place : "Station {$position}",
            'location' => $place !== '' ? $place : null,
            'mood' => trim((string) ($point['feeling'] ?? '')) ?: null,
            // The old data holds the placeholder «Nachricht 1», «Nachricht 2» … where there is no text.
            'notes' => $message !== '' && ! preg_match('/^Nachricht\s*\d+$/i', $message) ? $message : null,
            'day' => 1,
            'simulator_state' => $scene !== '' ? ['screen' => $scene] : null,
        ]);
        $stats['boards']++;

        $layers = [
            [AssetLayer::Middle, $this->boardImage($point['image'] ?? null)],
            [AssetLayer::Front, $this->relativeImage($point['imageForeground'] ?? null)],
            [AssetLayer::Back, $this->relativeImage($point['imageBackground'] ?? null)],
        ];

        foreach ($layers as [$layer, $relative]) {
            if ($relative === null) {
                continue;
            }
            if (($path = $this->copy($source, $relative, "journeys/{$journey->id}")) !== null) {
                $board->assets()->create(['layer' => $layer, 'path' => $path, 'position' => $stats['assets']++]);
            }
        }
    }

    /** @param array<string, int> $stats */
    private function importCharacters(string $source, Journey $journey, array &$stats): void
    {
        $position = 0;

        foreach (self::CHARACTERS as $name => $poses) {
            $character = $journey->characters()->create(['name' => $name, 'position' => ++$position]);
            $stats['characters']++;

            foreach ($poses as $pose => $relative) {
                if (($path = $this->copy($source, $relative, "journeys/{$journey->id}/characters")) !== null) {
                    $character->images()->create(['pose' => CharacterPose::from($pose), 'path' => $path]);
                    $stats['images']++;
                }
            }
        }
    }

    /** @param array<string, int> $stats */
    private function importBackdrops(string $source, Journey $journey, array &$stats): void
    {
        foreach (self::BACKDROPS as $index => [$relative, $layer, $speed, $align, $height, $repeat]) {
            if (($path = $this->copy($source, $relative, "journeys/{$journey->id}/backdrops")) !== null) {
                $journey->backdrops()->create([
                    'layer' => AssetLayer::from($layer),
                    'path' => $path,
                    'position' => $index + 1,
                    'speed' => $speed,
                    'align' => BackdropAlign::from($align),
                    'height' => $height,
                    'repeat' => $repeat,
                ]);
                $stats['backdrops']++;
            }
        }
    }

    /** The main image of a way point is named without folder: «haus-kevins-haus» lives in images/haus/. */
    private function boardImage(mixed $name): ?string
    {
        if (! is_string($name) || trim($name) === '') {
            return null;
        }

        $name = trim($name);
        $folder = explode('-', $name)[0];

        return "{$folder}/{$name}.png";
    }

    /** Foreground and background images are given with their folder and extension. */
    private function relativeImage(mixed $path): ?string
    {
        return is_string($path) && trim($path) !== '' ? ltrim(trim($path), '/') : null;
    }

    /** Copy an image of the old website to the private disk; null (and noted) if it is missing. */
    private function copy(string $source, string $relative, string $directory): ?string
    {
        // Never leave the images folder, whatever the data says.
        $file = realpath(rtrim($source, '/')."/images/{$relative}");
        $root = realpath(rtrim($source, '/').'/images');

        if ($file === false || $root === false || ! str_starts_with($file, $root.DIRECTORY_SEPARATOR) || ! is_file($file)) {
            $this->missing[] = $relative;

            return null;
        }

        $path = Storage::disk(BoardAsset::DISK)->putFile($directory, new File($file));
        $this->copied[] = $path;

        return $path;
    }
}
