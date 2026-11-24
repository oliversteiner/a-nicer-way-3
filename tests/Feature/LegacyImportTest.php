<?php

namespace Tests\Feature;

use App\Enums\AssetLayer;
use App\Enums\CharacterPose;
use App\Models\BoardAsset;
use App\Models\Journey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LegacyImportTest extends TestCase
{
    use RefreshDatabase;

    private string $source;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(BoardAsset::DISK);
        $this->source = storage_path('framework/testing/legacy-'.uniqid());
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->source);
        parent::tearDown();
    }

    /** Build a small copy of the old website; $files are the images that exist below images/. */
    private function website(array $waypoints, array $files): void
    {
        File::ensureDirectoryExists($this->source.'/data');
        File::put($this->source.'/data/defaultdata.json', json_encode(['data' => array_merge([['_id' => 'config', 'type' => 'Configuration']], $waypoints)]));

        foreach ($files as $relative) {
            File::ensureDirectoryExists(dirname($this->source.'/images/'.$relative));
            File::put($this->source.'/images/'.$relative, 'image:'.$relative);
        }
    }

    /** @return array<string, mixed> */
    private function point(int $sequence, array $override = []): array
    {
        return array_replace([
            '_id' => "TimeWayPoint-{$sequence}",
            'type' => 'TimeWayPoint',
            'sequence' => $sequence,
            'place' => "Ort {$sequence}",
            'feeling' => 'Gut',
            'message' => 'Nachricht 1',
            'image' => 'haus-kevins-haus',
            'hype' => ['scene' => ['sceneName' => 'kalender']],
        ], $override);
    }

    private function everyFile(): array
    {
        return [
            'haus/haus-kevins-haus.png', 'schmuck/schmuck-busstop.png', 'haus/haus-stadtbild.png',
            'character/character-kevin-warten.png', 'character/character-kevin-laufen.png', 'character/character-kevin-mobile.png',
            'character/character-kevin-winken.png', 'character/character-gruppe.png', 'character/character-bus.png',
            'character/character-bus-mit-kevin.png', 'papier/papierhintergrund.jpg', 'himmel/ANW_Berge.png',
        ];
    }

    private function import(string $email, array $options = []): \Illuminate\Testing\PendingCommand
    {
        return $this->artisan('journey:import-legacy', ['path' => $this->source, '--user' => $email] + $options);
    }

    public function test_it_imports_boards_images_characters_and_backdrops(): void
    {
        $user = User::factory()->create();
        $this->website([
            $this->point(2, ['place' => 'Zweiter', 'message' => 'Eine echte Notiz', 'imageForeground' => 'schmuck/schmuck-busstop.png', 'imageBackground' => 'haus/haus-stadtbild.png']),
            $this->point(1, ['place' => '', 'message' => '']),
        ], $this->everyFile());

        $this->import($user->email)->assertSuccessful();

        $journey = Journey::query()->where('slug', 'a-nicer-way')->firstOrFail();
        $this->assertSame($user->id, $journey->user_id);
        $this->assertNull($journey->published_at);

        $boards = $journey->boards()->get();
        $this->assertSame(['Station 1', 'Zweiter'], $boards->pluck('title')->all());
        $this->assertSame([null, 'Zweiter'], $boards->pluck('location')->all());
        $this->assertSame([null, 'Eine echte Notiz'], $boards->pluck('notes')->all());
        $this->assertSame(['Gut', 'Gut'], $boards->pluck('mood')->all());
        $this->assertSame(['screen' => 'kalender'], $boards[1]->simulator_state);

        $this->assertEqualsCanonicalizing([AssetLayer::Middle], $boards[0]->assets->pluck('layer')->all());
        $this->assertEqualsCanonicalizing([AssetLayer::Middle, AssetLayer::Front, AssetLayer::Back], $boards[1]->assets->pluck('layer')->all());

        $this->assertSame(['Kevin', 'Gruppe', 'Bus'], $journey->characters()->pluck('name')->all());
        $kevin = $journey->characters()->first();
        $this->assertEqualsCanonicalizing([CharacterPose::Idle, CharacterPose::Go, CharacterPose::Pause, CharacterPose::Greet], $kevin->images->pluck('pose')->all());

        $this->assertSame(2, $journey->backdrops()->count());
        $this->assertSame([0.2, 0.4], $journey->backdrops->pluck('speed')->all());

        foreach ($journey->boards as $board) {
            $board->assets->each(fn ($asset) => Storage::disk(BoardAsset::DISK)->assertExists($asset->path));
        }
    }

    public function test_the_title_defaults_to_a_nicer_way_without_quotes(): void
    {
        $user = User::factory()->create();
        $this->website([$this->point(1)], $this->everyFile());

        $this->import($user->email)->assertSuccessful();

        $this->assertSame('A Nicer Way', Journey::query()->firstOrFail()->title);
    }

    public function test_the_copied_files_are_the_originals(): void
    {
        $user = User::factory()->create();
        $this->website([$this->point(1)], $this->everyFile());

        $this->import($user->email)->assertSuccessful();

        $asset = BoardAsset::query()->firstOrFail();
        $this->assertSame('image:haus/haus-kevins-haus.png', Storage::disk(BoardAsset::DISK)->get($asset->path));
    }

    public function test_publish_option_publishes_the_journey(): void
    {
        $user = User::factory()->create();
        $this->website([$this->point(1)], $this->everyFile());

        $this->import($user->email, ['--publish' => true, '--slug' => 'demo2', '--title' => 'Meine Reise'])->assertSuccessful();

        $journey = Journey::query()->where('slug', 'demo2')->firstOrFail();
        $this->assertSame('Meine Reise', $journey->title);
        $this->assertNotNull($journey->published_at);
    }

    public function test_missing_images_are_skipped_and_reported(): void
    {
        $user = User::factory()->create();
        $this->website([$this->point(1)], ['haus/haus-kevins-haus.png']);

        $this->import($user->email)
            ->expectsOutputToContain('Files not found (skipped)')
            ->assertSuccessful();

        $this->assertSame(1, BoardAsset::query()->count());
        $this->assertSame(0, Journey::query()->firstOrFail()->backdrops()->count());
    }

    public function test_an_image_path_cannot_leave_the_images_folder(): void
    {
        $user = User::factory()->create();
        $this->website([$this->point(1, ['imageForeground' => '../data/defaultdata.json'])], $this->everyFile());

        $this->import($user->email)->assertSuccessful();

        $this->assertSame([AssetLayer::Middle], BoardAsset::query()->pluck('layer')->all());
    }

    public function test_an_existing_journey_is_not_replaced_without_the_option(): void
    {
        $user = User::factory()->create();
        $this->website([$this->point(1)], $this->everyFile());
        $this->import($user->email)->assertSuccessful();

        $this->import($user->email)->expectsOutputToContain('already exists')->assertFailed();

        $this->assertSame(1, Journey::query()->count());
    }

    public function test_replace_imports_again_and_removes_the_old_files(): void
    {
        $user = User::factory()->create();
        $this->website([$this->point(1)], $this->everyFile());
        $this->import($user->email)->assertSuccessful();
        $oldPath = BoardAsset::query()->firstOrFail()->path;

        $this->import($user->email, ['--replace' => true])->assertSuccessful();

        $this->assertSame(1, Journey::query()->count());
        $this->assertSame(1, BoardAsset::query()->count());
        Storage::disk(BoardAsset::DISK)->assertMissing($oldPath);
    }

    public function test_it_does_not_replace_the_journey_of_another_user(): void
    {
        $other = Journey::factory()->create(['slug' => 'a-nicer-way']);
        $user = User::factory()->create();
        $this->website([$this->point(1)], $this->everyFile());

        $this->import($user->email, ['--replace' => true])->expectsOutputToContain('another user')->assertFailed();

        $this->assertModelExists($other);
    }

    public function test_it_needs_a_known_user(): void
    {
        $this->website([$this->point(1)], $this->everyFile());

        $this->artisan('journey:import-legacy', ['path' => $this->source])->expectsOutputToContain('--user')->assertFailed();
        $this->import('nobody@example.com')->expectsOutputToContain('No user')->assertFailed();
        $this->assertSame(0, Journey::query()->count());
    }

    public function test_it_rejects_a_folder_that_is_not_the_old_website(): void
    {
        $user = User::factory()->create();
        File::ensureDirectoryExists($this->source);

        $this->import($user->email)->expectsOutputToContain('Not an old website folder')->assertFailed();
    }

    public function test_it_rejects_broken_data(): void
    {
        $user = User::factory()->create();
        File::ensureDirectoryExists($this->source.'/data');

        File::put($this->source.'/data/defaultdata.json', '{broken');
        $this->import($user->email)->expectsOutputToContain('not valid')->assertFailed();

        File::put($this->source.'/data/defaultdata.json', json_encode(['data' => [['type' => 'Configuration']]]));
        $this->import($user->email)->expectsOutputToContain('no time way points')->assertFailed();

        $this->assertSame(0, Journey::query()->count());
    }

    public function test_a_failure_in_the_middle_leaves_neither_rows_nor_files_behind(): void
    {
        $user = User::factory()->create();
        $this->website([$this->point(1)], $this->everyFile());
        // Fail after the board images were copied, while the characters are imported.
        \App\Models\CharacterImage::creating(fn () => throw new \RuntimeException('disk full'));

        try {
            $this->import($user->email);
            $this->fail('The import should have failed.');
        } catch (\RuntimeException $e) {
            $this->assertSame('disk full', $e->getMessage());
        }

        $this->assertSame(0, Journey::query()->count());
        $this->assertSame(0, BoardAsset::query()->count());
        $this->assertSame([], Storage::disk(BoardAsset::DISK)->allFiles());
    }
}
