<?php

namespace Tests\Feature;

use App\Models\Journey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EditorJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/editor/journeys')->assertRedirect('/login');
        $this->post('/editor/journeys', ['title' => 'x', 'published' => false])->assertRedirect('/login');
    }

    public function test_index_lists_only_own_journeys(): void
    {
        $user = User::factory()->create();
        $own = Journey::factory()->for($user)->create();
        Journey::factory()->create();

        $this->actingAs($user)->get('/editor/journeys')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Journeys/Index')
                ->has('journeys', 1)
                ->where('journeys.0.id', $own->id));
    }

    public function test_user_can_create_a_journey_with_a_unique_slug(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/editor/journeys', ['title' => 'Mein Tag', 'published' => true]);
        $this->actingAs($user)->post('/editor/journeys', ['title' => 'Mein Tag', 'published' => false]);

        $this->assertSame(['mein-tag', 'mein-tag-2'], Journey::orderBy('id')->pluck('slug')->all());
        $this->assertNotNull(Journey::where('slug', 'mein-tag')->value('published_at'));
        $this->assertNull(Journey::where('slug', 'mein-tag-2')->value('published_at'));
    }

    public function test_title_is_required(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/editor/journeys', ['title' => '', 'published' => false])
            ->assertSessionHasErrors('title');
    }

    public function test_owner_can_update_and_unpublish_a_journey(): void
    {
        $journey = Journey::factory()->create();

        $this->actingAs($journey->user)
            ->put("/editor/journeys/{$journey->id}", ['title' => 'Neu', 'description' => 'D', 'published' => false])
            ->assertRedirect();

        $journey->refresh();
        $this->assertSame('Neu', $journey->title);
        $this->assertNull($journey->published_at);
    }

    public function test_publication_date_is_kept_while_published(): void
    {
        $journey = Journey::factory()->create(['published_at' => now()->subWeek()]);
        $before = $journey->published_at;

        $this->actingAs($journey->user)
            ->put("/editor/journeys/{$journey->id}", ['title' => 'Neu', 'published' => true]);

        $this->assertTrue($journey->fresh()->published_at->equalTo($before));
    }

    public function test_other_users_cannot_see_edit_or_delete_a_journey(): void
    {
        $journey = Journey::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($other)->get("/editor/journeys/{$journey->id}")->assertForbidden();
        $this->actingAs($other)->put("/editor/journeys/{$journey->id}", ['title' => 'x', 'published' => false])->assertForbidden();
        $this->actingAs($other)->delete("/editor/journeys/{$journey->id}")->assertForbidden();
        $this->assertModelExists($journey);
    }

    public function test_owner_can_delete_a_journey(): void
    {
        $journey = Journey::factory()->create();

        $this->actingAs($journey->user)->delete("/editor/journeys/{$journey->id}")->assertRedirect('/editor/journeys');

        $this->assertModelMissing($journey);
    }

    public function test_edit_page_contains_boards_in_order(): void
    {
        $journey = Journey::factory()->create();
        $journey->boards()->create(['position' => 2, 'title' => 'B', 'day' => 1, 'time' => '08:30:00', 'simulator_state' => ['screen' => 'Karte']]);
        $journey->boards()->create(['position' => 1, 'title' => 'A', 'day' => 1]);

        $this->actingAs($journey->user)->get("/editor/journeys/{$journey->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Journeys/Edit')
                ->where('journey.boards.0.title', 'A')
                ->where('journey.boards.1.time', '08:30')
                ->where('journey.boards.1.simulator_screen', 'Karte'));
    }
}
