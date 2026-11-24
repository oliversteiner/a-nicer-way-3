<?php

namespace Tests\Feature;

use App\Enums\AssetLayer;
use App\Models\Board;
use App\Models\BoardAsset;
use App\Models\Journey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BoardAssetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(BoardAsset::DISK);
    }

    private function upload(Board $board, UploadedFile $file, string $layer = 'back', ?User $as = null)
    {
        return $this->actingAs($as ?? $board->journey->user)
            ->post("/editor/boards/{$board->id}/assets", ['layer' => $layer, 'image' => $file]);
    }

    private function assetWithFile(Board $board, string $layer = 'middle'): BoardAsset
    {
        $path = UploadedFile::fake()->image('x.png')->store("journeys/{$board->journey_id}", BoardAsset::DISK);

        return BoardAsset::factory()->for($board)->create(['layer' => $layer, 'path' => $path]);
    }

    public function test_owner_can_upload_images_in_order(): void
    {
        $board = Board::factory()->create();

        $this->upload($board, UploadedFile::fake()->image('a.png'))->assertRedirect();
        $this->upload($board, UploadedFile::fake()->image('b.jpg'), 'front');

        $assets = $board->assets()->get();
        $this->assertSame([1, 2], $assets->pluck('position')->all());
        $this->assertSame([AssetLayer::Back, AssetLayer::Front], $assets->pluck('layer')->all());
        $assets->each(fn ($asset) => Storage::disk(BoardAsset::DISK)->assertExists($asset->path));
    }

    public function test_svg_and_webp_are_accepted(): void
    {
        $board = Board::factory()->create();
        $svg = UploadedFile::fake()->createWithContent('a.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"></svg>');

        $this->upload($board, $svg)->assertSessionHasNoErrors();
        $this->upload($board, UploadedFile::fake()->image('b.webp'))->assertSessionHasNoErrors();

        $this->assertSame(2, $board->assets()->count());
    }

    public function test_invalid_uploads_are_rejected(): void
    {
        $board = Board::factory()->create();

        $this->upload($board, UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'))->assertSessionHasErrors('image');
        $this->upload($board, UploadedFile::fake()->image('big.png')->size(5121))->assertSessionHasErrors('image');
        $this->upload($board, UploadedFile::fake()->image('a.png'), 'sky')->assertSessionHasErrors('layer');
        $this->assertSame(0, $board->assets()->count());
    }

    public function test_other_users_cannot_upload_or_delete(): void
    {
        $board = Board::factory()->create();
        $asset = $this->assetWithFile($board);
        $other = User::factory()->create();

        $this->upload($board, UploadedFile::fake()->image('a.png'), 'back', $other)->assertForbidden();
        $this->actingAs($other)->delete("/editor/assets/{$asset->id}")->assertForbidden();
        $this->assertModelExists($asset);
    }

    public function test_deleting_an_asset_removes_its_file(): void
    {
        $asset = $this->assetWithFile(Board::factory()->create());

        $this->actingAs($asset->board->journey->user)->delete("/editor/assets/{$asset->id}")->assertRedirect();

        $this->assertModelMissing($asset);
        Storage::disk(BoardAsset::DISK)->assertMissing($asset->path);
    }

    public function test_deleting_a_board_or_journey_removes_files(): void
    {
        $journey = Journey::factory()->create();
        $first = $this->assetWithFile(Board::factory()->for($journey)->create(['position' => 1]));
        $second = $this->assetWithFile(Board::factory()->for($journey)->create(['position' => 2]));

        $this->actingAs($journey->user)->delete("/editor/boards/{$first->board_id}");
        Storage::disk(BoardAsset::DISK)->assertMissing($first->path);
        Storage::disk(BoardAsset::DISK)->assertExists($second->path);

        $this->actingAs($journey->user)->delete("/editor/journeys/{$journey->id}");
        Storage::disk(BoardAsset::DISK)->assertMissing($second->path);
    }

    public function test_deleting_a_user_removes_the_files_of_their_journeys(): void
    {
        $asset = $this->assetWithFile(Board::factory()->create());

        $asset->board->journey->user->delete();

        $this->assertDatabaseCount('board_assets', 0);
        Storage::disk(BoardAsset::DISK)->assertMissing($asset->path);
    }

    public function test_published_images_are_public_with_hardening_headers(): void
    {
        $asset = $this->assetWithFile(Board::factory()->create());

        $this->get("/assets/{$asset->id}")
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; sandbox");
    }

    public function test_images_of_drafts_are_only_visible_to_the_owner(): void
    {
        $journey = Journey::factory()->draft()->create();
        $asset = $this->assetWithFile(Board::factory()->for($journey)->create());

        $this->get("/assets/{$asset->id}")->assertNotFound();
        $this->actingAs(User::factory()->create())->get("/assets/{$asset->id}")->assertNotFound();
        $this->actingAs($journey->user)->get("/assets/{$asset->id}")->assertOk();
    }

    public function test_missing_file_returns_404(): void
    {
        $asset = BoardAsset::factory()->create(['path' => 'journeys/1/gone.png']);

        $this->get("/assets/{$asset->id}")->assertNotFound();
    }

    public function test_public_page_exposes_asset_urls(): void
    {
        $board = Board::factory()->create();
        $asset = $this->assetWithFile($board, 'front');

        $this->get("/journeys/{$board->journey->slug}")->assertInertia(fn (Assert $page) => $page
            ->where('journey.boards.0.assets.0.layer', 'front')
            ->where('journey.boards.0.assets.0.url', route('assets.show', $asset)));
    }

    public function test_editor_page_exposes_asset_ids_and_urls(): void
    {
        $board = Board::factory()->create();
        $asset = $this->assetWithFile($board, 'front');

        $this->actingAs($board->journey->user)->get("/editor/journeys/{$board->journey_id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('journey.boards.0.assets.0.id', $asset->id)
                ->where('journey.boards.0.assets.0.url', route('assets.show', $asset)));
    }
}
