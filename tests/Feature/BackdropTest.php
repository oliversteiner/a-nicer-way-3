<?php

namespace Tests\Feature;

use App\Enums\AssetLayer;
use App\Enums\BackdropAlign;
use App\Models\BoardAsset;
use App\Models\Journey;
use App\Models\JourneyBackdrop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BackdropTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(BoardAsset::DISK);
    }

    private function backdropWithFile(?Journey $journey = null): JourneyBackdrop
    {
        $journey ??= Journey::factory()->create();
        $path = UploadedFile::fake()->image('x.png')->store("journeys/{$journey->id}/backdrops", BoardAsset::DISK);

        return JourneyBackdrop::factory()->for($journey)->create(['path' => $path]);
    }

    private function upload(Journey $journey, string $layer, UploadedFile $file, ?User $as = null)
    {
        return $this->actingAs($as ?? $journey->user)->post("/editor/journeys/{$journey->id}/backdrops", ['layer' => $layer, 'image' => $file]);
    }

    /** @return array<string, mixed> */
    private function settings(array $override = []): array
    {
        return array_replace(['layer' => 'back', 'speed' => 0.4, 'align' => 'top', 'height' => 60, 'repeat' => false], $override);
    }

    public function test_an_upload_creates_a_backdrop_with_defaults_for_its_layer(): void
    {
        $journey = Journey::factory()->create();

        $this->upload($journey, 'back', UploadedFile::fake()->image('sky.png'))->assertRedirect();
        $this->upload($journey, 'front', UploadedFile::fake()->image('trees.png'));

        [$back, $front] = $journey->backdrops()->get()->all();
        $this->assertSame([1, 2], [$back->position, $front->position]);
        $this->assertEquals(0.3, $back->speed);
        $this->assertTrue($back->repeat);
        $this->assertEquals(1.5, $front->speed);
        $this->assertFalse($front->repeat);
        $this->assertSame(BackdropAlign::Bottom, $back->align);
        Storage::disk(BoardAsset::DISK)->assertExists($back->path);
    }

    public function test_invalid_uploads_are_rejected(): void
    {
        $journey = Journey::factory()->create();

        $this->upload($journey, 'back', UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'))->assertSessionHasErrors('image');
        $this->upload($journey, 'back', UploadedFile::fake()->image('big.png')->size(5121))->assertSessionHasErrors('image');
        $this->upload($journey, 'middle', UploadedFile::fake()->image('a.png'))->assertSessionHasErrors('layer');
        $this->assertSame(0, $journey->backdrops()->count());
    }

    public function test_the_settings_can_be_changed(): void
    {
        $backdrop = $this->backdropWithFile();

        $this->actingAs($backdrop->journey->user)->put("/editor/backdrops/{$backdrop->id}", $this->settings(['layer' => 'front', 'speed' => 1.25]))->assertRedirect();

        $backdrop->refresh();
        $this->assertSame(AssetLayer::Front, $backdrop->layer);
        $this->assertEquals(1.25, $backdrop->speed);
        $this->assertSame(BackdropAlign::Top, $backdrop->align);
        $this->assertSame(60, $backdrop->height);
        $this->assertFalse($backdrop->repeat);
    }

    public function test_settings_are_validated(): void
    {
        $backdrop = $this->backdropWithFile();

        $this->actingAs($backdrop->journey->user)
            ->put("/editor/backdrops/{$backdrop->id}", $this->settings(['layer' => 'middle', 'speed' => 9, 'align' => 'left', 'height' => 5, 'repeat' => 'maybe']))
            ->assertSessionHasErrors(['layer', 'speed', 'align', 'height', 'repeat']);
    }

    public function test_other_users_cannot_manage_backdrops(): void
    {
        $backdrop = $this->backdropWithFile();
        $other = User::factory()->create();

        $this->upload($backdrop->journey, 'back', UploadedFile::fake()->image('a.png'), $other)->assertForbidden();
        $this->actingAs($other)->put("/editor/backdrops/{$backdrop->id}", $this->settings())->assertForbidden();
        $this->actingAs($other)->delete("/editor/backdrops/{$backdrop->id}")->assertForbidden();
        $this->assertModelExists($backdrop);
    }

    public function test_deleting_a_backdrop_removes_its_file(): void
    {
        $backdrop = $this->backdropWithFile();

        $this->actingAs($backdrop->journey->user)->delete("/editor/backdrops/{$backdrop->id}")->assertRedirect();

        $this->assertModelMissing($backdrop);
        Storage::disk(BoardAsset::DISK)->assertMissing($backdrop->path);
    }

    public function test_deleting_a_journey_or_a_user_removes_the_backdrop_files(): void
    {
        $first = $this->backdropWithFile();
        $second = $this->backdropWithFile();

        $first->journey->delete();
        $second->journey->user->delete();

        Storage::disk(BoardAsset::DISK)->assertMissing($first->path);
        Storage::disk(BoardAsset::DISK)->assertMissing($second->path);
        $this->assertDatabaseCount('journey_backdrops', 0);
    }

    public function test_published_backdrops_are_public_with_hardening_headers(): void
    {
        $backdrop = $this->backdropWithFile();

        $this->get("/backdrops/{$backdrop->id}")
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; sandbox");
    }

    public function test_backdrops_of_drafts_are_only_visible_to_the_owner(): void
    {
        $journey = Journey::factory()->draft()->create();
        $backdrop = $this->backdropWithFile($journey);

        $this->get("/backdrops/{$backdrop->id}")->assertNotFound();
        $this->actingAs(User::factory()->create())->get("/backdrops/{$backdrop->id}")->assertNotFound();
        $this->actingAs($journey->user)->get("/backdrops/{$backdrop->id}")->assertOk();
    }

    public function test_a_missing_file_returns_404(): void
    {
        $backdrop = JourneyBackdrop::factory()->create(['path' => 'journeys/1/backdrops/gone.png']);

        $this->get("/backdrops/{$backdrop->id}")->assertNotFound();
    }

    public function test_the_public_page_lists_backdrops_in_order_with_their_settings(): void
    {
        $journey = Journey::factory()->create();
        $second = JourneyBackdrop::factory()->for($journey)->create(['position' => 2, 'speed' => 0.5, 'align' => BackdropAlign::Top, 'height' => 40, 'repeat' => false]);
        $first = JourneyBackdrop::factory()->for($journey)->create(['position' => 1]);

        $this->get("/journeys/{$journey->slug}")->assertInertia(fn (Assert $page) => $page
            ->where('journey.backdrops.0.id', $first->id)
            ->where('journey.backdrops.1.url', route('backdrops.show', $second))
            ->where('journey.backdrops.1.speed', 0.5)
            ->where('journey.backdrops.1.align', 'top')
            ->where('journey.backdrops.1.height', 40)
            ->where('journey.backdrops.1.repeat', false));
    }

    public function test_the_editor_page_lists_the_backdrops(): void
    {
        $backdrop = $this->backdropWithFile();

        $this->actingAs($backdrop->journey->user)->get("/editor/journeys/{$backdrop->journey_id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('journey.backdrops.0.id', $backdrop->id)
                ->where('journey.backdrops.0.layer', 'back')
                ->where('journey.backdrops.0.url', route('backdrops.show', $backdrop)));
    }
}
