<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Journey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class JourneyPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_journey_is_public_and_lists_boards_in_order(): void
    {
        $journey = Journey::factory()->create(['slug' => 'demo']);
        Board::factory()->for($journey)->create(['position' => 2, 'title' => 'Second']);
        Board::factory()->for($journey)->create(['position' => 1, 'title' => 'First']);

        $this->get('/journeys/demo')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Journeys/Show')
                ->where('journey.slug', 'demo')
                ->has('journey.boards', 2)
                ->where('journey.boards.0.title', 'First'));
    }

    public function test_draft_is_hidden_from_guests_and_other_users(): void
    {
        Journey::factory()->draft()->create(['slug' => 'draft']);

        $this->get('/journeys/draft')->assertNotFound();
        $this->actingAs(User::factory()->create())->get('/journeys/draft')->assertNotFound();
    }

    public function test_owner_can_see_own_draft(): void
    {
        $journey = Journey::factory()->draft()->create(['slug' => 'draft']);

        $this->actingAs($journey->user)->get('/journeys/draft')->assertOk();
    }

    public function test_unknown_slug_returns_404(): void
    {
        $this->get('/journeys/nope')->assertNotFound();
    }
}
