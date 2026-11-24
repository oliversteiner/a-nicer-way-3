<?php

namespace Tests\Feature;

use App\Enums\AssetLayer;
use App\Models\Board;
use App\Models\BoardAsset;
use App\Models\Journey;
use Database\Seeders\JourneySeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JourneyModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_boards_are_ordered_by_position(): void
    {
        $journey = Journey::factory()->create();
        Board::factory()->for($journey)->create(['position' => 2]);
        Board::factory()->for($journey)->create(['position' => 1]);

        $this->assertSame([1, 2], $journey->boards->pluck('position')->all());
    }

    public function test_position_is_unique_per_journey(): void
    {
        $journey = Journey::factory()->create();
        Board::factory()->for($journey)->create(['position' => 1]);

        $this->expectException(QueryException::class);
        Board::factory()->for($journey)->create(['position' => 1]);
    }

    public function test_published_scope_excludes_drafts(): void
    {
        $published = Journey::factory()->create();
        Journey::factory()->draft()->create();

        $this->assertSame([$published->id], Journey::published()->pluck('id')->all());
    }

    public function test_board_casts_and_asset_layer(): void
    {
        $board = Board::factory()->create(['is_night' => 1, 'simulator_state' => ['screen' => 'home']]);
        $asset = BoardAsset::factory()->for($board)->create(['layer' => 'front']);

        $this->assertTrue($board->fresh()->is_night);
        $this->assertSame(['screen' => 'home'], $board->fresh()->simulator_state);
        $this->assertSame(AssetLayer::Front, $asset->fresh()->layer);
    }

    public function test_deleting_a_journey_removes_boards_and_assets(): void
    {
        $asset = BoardAsset::factory()->create();
        $asset->board->journey->delete();

        $this->assertDatabaseCount('boards', 0);
        $this->assertDatabaseCount('board_assets', 0);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(JourneySeeder::class);
        $this->seed(JourneySeeder::class);

        $this->assertDatabaseCount('journeys', 1);
        $this->assertDatabaseCount('boards', 11);
    }
}
