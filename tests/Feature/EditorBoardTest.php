<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Journey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorBoardTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function payload(array $override = []): array
    {
        return $override + ['title' => 'Titel', 'day' => 1, 'is_night' => false];
    }

    public function test_new_boards_are_appended(): void
    {
        $journey = Journey::factory()->create();

        $this->actingAs($journey->user)->post("/editor/journeys/{$journey->id}/boards", $this->payload());
        $this->actingAs($journey->user)->post("/editor/journeys/{$journey->id}/boards", $this->payload(['title' => 'Zwei']));

        $this->assertSame([1, 2], $journey->boards()->pluck('position')->all());
    }

    public function test_board_is_validated(): void
    {
        $journey = Journey::factory()->create();

        $this->actingAs($journey->user)
            ->post("/editor/journeys/{$journey->id}/boards", ['day' => 0, 'time' => '25:99', 'is_night' => false])
            ->assertSessionHasErrors(['day', 'time']);
    }

    public function test_simulator_screen_is_stored_in_simulator_state(): void
    {
        $journey = Journey::factory()->create();

        $this->actingAs($journey->user)
            ->post("/editor/journeys/{$journey->id}/boards", $this->payload(['simulator_screen' => 'Fahrplan', 'time' => '07:15']));

        $board = $journey->boards()->first();
        $this->assertSame(['screen' => 'Fahrplan'], $board->simulator_state);
        $this->assertStringStartsWith('07:15', $board->time);
    }

    public function test_clearing_the_screen_keeps_other_simulator_state(): void
    {
        $board = Board::factory()->create(['simulator_state' => ['screen' => 'Alt', 'extra' => 1]]);

        $this->actingAs($board->journey->user)
            ->put("/editor/boards/{$board->id}", $this->payload(['simulator_screen' => '']));

        $this->assertSame(['extra' => 1], $board->fresh()->simulator_state);
    }

    public function test_owner_can_update_a_board(): void
    {
        $board = Board::factory()->create();

        $this->actingAs($board->journey->user)
            ->put("/editor/boards/{$board->id}", $this->payload(['title' => 'Geändert', 'is_night' => true, 'day' => 2]));

        $board->refresh();
        $this->assertSame('Geändert', $board->title);
        $this->assertTrue($board->is_night);
        $this->assertSame(2, $board->day);
    }

    public function test_other_users_cannot_change_boards(): void
    {
        $board = Board::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($other)->put("/editor/boards/{$board->id}", $this->payload())->assertForbidden();
        $this->actingAs($other)->delete("/editor/boards/{$board->id}")->assertForbidden();
        $this->actingAs($other)->post("/editor/boards/{$board->id}/move", ['direction' => 'up'])->assertForbidden();
        $this->actingAs($other)->post("/editor/journeys/{$board->journey_id}/boards", $this->payload())->assertForbidden();
    }

    public function test_deleting_a_board_closes_the_gap(): void
    {
        $journey = Journey::factory()->create();
        $boards = collect([1, 2, 3])->map(fn ($p) => Board::factory()->for($journey)->create(['position' => $p, 'title' => "B$p"]));

        $this->actingAs($journey->user)->delete("/editor/boards/{$boards[1]->id}");

        $this->assertSame(['B1' => 1, 'B3' => 2], $journey->boards()->pluck('position', 'title')->all());
    }

    public function test_boards_can_be_moved_up_and_down(): void
    {
        $journey = Journey::factory()->create();
        $a = Board::factory()->for($journey)->create(['position' => 1, 'title' => 'A']);
        $b = Board::factory()->for($journey)->create(['position' => 2, 'title' => 'B']);

        $this->actingAs($journey->user)->post("/editor/boards/{$b->id}/move", ['direction' => 'up']);
        $this->assertSame(['B', 'A'], $journey->boards()->pluck('title')->all());

        $this->actingAs($journey->user)->post("/editor/boards/{$b->id}/move", ['direction' => 'down']);
        $this->assertSame(['A', 'B'], $journey->boards()->pluck('title')->all());
    }

    public function test_moving_the_first_board_up_changes_nothing(): void
    {
        $journey = Journey::factory()->create();
        $a = Board::factory()->for($journey)->create(['position' => 1]);

        $this->actingAs($journey->user)->post("/editor/boards/{$a->id}/move", ['direction' => 'up'])->assertRedirect();

        $this->assertSame(1, $a->fresh()->position);
    }

    public function test_move_requires_a_valid_direction(): void
    {
        $board = Board::factory()->create();

        $this->actingAs($board->journey->user)
            ->post("/editor/boards/{$board->id}/move", ['direction' => 'sideways'])
            ->assertSessionHasErrors('direction');
    }
}
