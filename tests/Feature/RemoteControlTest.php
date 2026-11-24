<?php

namespace Tests\Feature;

use App\Events\RemoteCommand;
use App\Events\RemoteState;
use App\Models\Board;
use App\Models\Journey;
use App\Models\User;
use App\Support\RemoteSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RemoteControlTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function state(array $override = []): array
    {
        return array_replace([
            'index' => 1,
            'count' => 3,
            'board' => ['position' => 2, 'title' => 'Zug', 'notes' => 'Notiz', 'day' => 1, 'time' => '08:00', 'location' => 'Bahn'],
            'characters' => [['id' => 7, 'name' => 'Kevin'], ['id' => 8, 'name' => 'Bus']],
            'character_id' => 7,
            'character_pose' => 'go',
            'timer' => ['remaining' => 120, 'total' => 300, 'running' => true, 'nearEnd' => false, 'timeOut' => false],
        ], $override);
    }

    /** @return array{remote_token: string, presenter_token: string} */
    private function pairing(?Journey $journey = null): array
    {
        return RemoteSession::create($journey ?? Journey::factory()->create());
    }

    public function test_pairing_creates_tokens_and_a_remote_url(): void
    {
        $journey = Journey::factory()->create(['slug' => 'demo']);

        $response = $this->postJson('/journeys/demo/remote')->assertOk();

        $this->assertNotNull(RemoteSession::find($response['remote_token']));
        $this->assertSame(route('remote.show', $response['remote_token']), $response['remote_url']);
        $this->assertNotSame($response['remote_token'], $response['presenter_token']);
    }

    public function test_the_presenter_token_is_not_stored_in_plain_text(): void
    {
        $tokens = $this->pairing();

        $this->assertStringNotContainsString($tokens['presenter_token'], json_encode(RemoteSession::find($tokens['remote_token'])));
    }

    public function test_drafts_can_only_be_paired_by_the_owner(): void
    {
        $journey = Journey::factory()->draft()->create(['slug' => 'draft']);

        $this->postJson('/journeys/draft/remote')->assertNotFound();
        $this->actingAs(User::factory()->create())->postJson('/journeys/draft/remote')->assertNotFound();
        $this->actingAs($journey->user)->postJson('/journeys/draft/remote')->assertOk();
    }

    public function test_the_remote_page_shows_the_journey_for_a_valid_token(): void
    {
        $journey = Journey::factory()->create(['title' => 'Meine Reise']);
        Board::factory()->for($journey)->create(['position' => 1, 'title' => 'Start']);
        $tokens = $this->pairing($journey);

        $this->get("/remote/{$tokens['remote_token']}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Remote/Show')
                ->where('token', $tokens['remote_token'])
                ->where('journey.title', 'Meine Reise')
                ->where('journey.boards.0.title', 'Start'));
    }

    public function test_an_unknown_token_is_not_found(): void
    {
        $this->get('/remote/nope')->assertNotFound();
        $this->postJson('/remote/nope/command', ['action' => 'next'])->assertNotFound();
    }

    public function test_commands_are_broadcast_to_the_presentation(): void
    {
        Event::fake([RemoteCommand::class]);
        $token = $this->pairing()['remote_token'];

        $this->postJson("/remote/{$token}/command", ['action' => 'next'])->assertNoContent();
        $this->postJson("/remote/{$token}/command", ['action' => 'goto', 'value' => 4])->assertNoContent();

        Event::assertDispatched(RemoteCommand::class, fn ($e) => $e->token === $token && $e->action === 'next' && $e->value === null);
        Event::assertDispatched(RemoteCommand::class, fn ($e) => $e->action === 'goto' && $e->value === 4);
    }

    public function test_commands_are_validated(): void
    {
        Event::fake([RemoteCommand::class]);
        $token = $this->pairing()['remote_token'];

        $this->postJson("/remote/{$token}/command", ['action' => 'explode'])->assertJsonValidationErrors('action');
        $this->postJson("/remote/{$token}/command", ['action' => 'goto'])->assertJsonValidationErrors('value');
        $this->postJson("/remote/{$token}/command", ['action' => 'goto', 'value' => 0])->assertJsonValidationErrors('value');

        Event::assertNotDispatched(RemoteCommand::class);
    }

    public function test_the_presenter_can_publish_state(): void
    {
        Event::fake([RemoteState::class]);
        $tokens = $this->pairing();

        $this->postJson("/remote/{$tokens['remote_token']}/state", ['presenter_token' => $tokens['presenter_token'], 'state' => $this->state()])
            ->assertNoContent();

        Event::assertDispatched(RemoteState::class, fn ($e) => $e->token === $tokens['remote_token'] && $e->state['board']['title'] === 'Zug');
    }

    public function test_character_commands_are_broadcast(): void
    {
        Event::fake([RemoteCommand::class]);
        $token = $this->pairing()['remote_token'];

        $this->postJson("/remote/{$token}/command", ['action' => 'character-select', 'value' => 7])->assertNoContent();
        $this->postJson("/remote/{$token}/command", ['action' => 'character-pose', 'pose' => 'greet'])->assertNoContent();

        Event::assertDispatched(RemoteCommand::class, fn ($e) => $e->action === 'character-select' && $e->value === 7 && $e->pose === null);
        Event::assertDispatched(RemoteCommand::class, fn ($e) => $e->action === 'character-pose' && $e->pose === 'greet' && $e->value === null);
    }

    public function test_character_commands_are_validated(): void
    {
        Event::fake([RemoteCommand::class]);
        $token = $this->pairing()['remote_token'];

        $this->postJson("/remote/{$token}/command", ['action' => 'character-select'])->assertJsonValidationErrors('value');
        $this->postJson("/remote/{$token}/command", ['action' => 'character-pose'])->assertJsonValidationErrors('pose');
        $this->postJson("/remote/{$token}/command", ['action' => 'character-pose', 'pose' => 'fly'])->assertJsonValidationErrors('pose');

        Event::assertNotDispatched(RemoteCommand::class);
    }

    public function test_commands_carry_the_pose_in_their_payload(): void
    {
        $this->assertSame(['action' => 'character-pose', 'value' => null, 'pose' => 'go'], (new RemoteCommand('abc', 'character-pose', null, 'go'))->broadcastWith());
    }

    public function test_state_includes_the_characters_and_the_current_pose(): void
    {
        Event::fake([RemoteState::class]);
        $tokens = $this->pairing();

        $this->postJson("/remote/{$tokens['remote_token']}/state", ['presenter_token' => $tokens['presenter_token'], 'state' => $this->state(['characters' => [], 'character_id' => null, 'character_pose' => 'idle'])])
            ->assertNoContent();
        $this->postJson("/remote/{$tokens['remote_token']}/state", ['presenter_token' => $tokens['presenter_token'], 'state' => $this->state()])
            ->assertNoContent();

        Event::assertDispatched(RemoteState::class, fn ($e) => $e->state['character_id'] === 7 && $e->state['character_pose'] === 'go' && $e->state['characters'][1]['name'] === 'Bus');
    }

    public function test_invalid_character_state_is_rejected(): void
    {
        $tokens = $this->pairing();

        $this->postJson("/remote/{$tokens['remote_token']}/state", ['presenter_token' => $tokens['presenter_token'], 'state' => $this->state(['character_pose' => 'fly', 'characters' => [['id' => 'x']]])])
            ->assertJsonValidationErrors(['state.character_pose', 'state.characters.0.id', 'state.characters.0.name']);
    }

    public function test_state_needs_the_presenter_token(): void
    {
        Event::fake([RemoteState::class]);
        $tokens = $this->pairing();

        $this->postJson("/remote/{$tokens['remote_token']}/state", ['presenter_token' => 'wrong', 'state' => $this->state()])->assertForbidden();
        $this->postJson("/remote/{$tokens['remote_token']}/state", ['presenter_token' => $tokens['remote_token'], 'state' => $this->state()])->assertForbidden();

        Event::assertNotDispatched(RemoteState::class);
    }

    public function test_state_is_validated(): void
    {
        $tokens = $this->pairing();

        $this->postJson("/remote/{$tokens['remote_token']}/state", ['presenter_token' => $tokens['presenter_token'], 'state' => ['index' => 'x']])
            ->assertJsonValidationErrors(['state.index', 'state.count', 'state.timer']);
    }

    public function test_the_presenter_can_end_the_pairing(): void
    {
        $tokens = $this->pairing();

        $this->deleteJson("/remote/{$tokens['remote_token']}", ['presenter_token' => 'wrong'])->assertForbidden();
        $this->assertNotNull(RemoteSession::find($tokens['remote_token']));

        $this->deleteJson("/remote/{$tokens['remote_token']}", ['presenter_token' => $tokens['presenter_token']])->assertNoContent();
        $this->assertNull(RemoteSession::find($tokens['remote_token']));
        $this->get("/remote/{$tokens['remote_token']}")->assertNotFound();
    }

    public function test_commands_are_rate_limited_per_pairing(): void
    {
        Event::fake([RemoteCommand::class]);
        $token = $this->pairing()['remote_token'];

        foreach (range(1, 240) as $i) {
            $this->postJson("/remote/{$token}/command", ['action' => 'next'])->assertNoContent();
        }

        $this->postJson("/remote/{$token}/command", ['action' => 'next'])->assertTooManyRequests();
    }

    public function test_events_use_public_channels_named_after_the_token(): void
    {
        $this->assertSame('remote.abc.commands', (new RemoteCommand('abc', 'next'))->broadcastOn()->name);
        $this->assertSame('remote.abc.state', (new RemoteState('abc', []))->broadcastOn()->name);
        $this->assertSame(['action' => 'goto', 'value' => 3, 'pose' => null], (new RemoteCommand('abc', 'goto', 3))->broadcastWith());
    }
}
