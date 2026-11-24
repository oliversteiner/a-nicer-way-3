<?php

namespace App\Http\Controllers;

use App\Enums\CharacterPose;
use App\Events\RemoteCommand;
use App\Events\RemoteState;
use App\Models\Journey;
use App\Support\RemoteSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class RemoteController extends Controller
{
    /** Start a pairing for a presentation the caller may see. */
    public function pair(Request $request, Journey $journey): JsonResponse
    {
        $isOwner = $request->user()?->id === $journey->user_id;

        abort_unless($isOwner || ($journey->published_at?->isPast() ?? false), 404);

        $tokens = RemoteSession::create($journey);

        return response()->json($tokens + ['remote_url' => route('remote.show', $tokens['remote_token'])]);
    }

    /** The remote control page, opened from the QR code. */
    public function show(string $token): InertiaResponse
    {
        $session = RemoteSession::find($token);
        abort_if($session === null, 404);

        $journey = Journey::query()->with('boards')->findOrFail($session['journey_id']);

        return Inertia::render('Remote/Show', [
            'token' => $token,
            'journey' => [
                'title' => $journey->title,
                'boards' => $journey->boards->map(fn ($board) => [
                    'position' => $board->position,
                    'title' => $board->title,
                ])->values(),
            ],
        ]);
    }

    /** A command from the remote control; the presentation listens for it. */
    public function command(Request $request, string $token): Response
    {
        abort_if(RemoteSession::find($token) === null, 404);

        $data = $request->validate([
            'action' => ['required', 'in:next,previous,goto,timer-toggle,timer-reset,character-select,character-pose,hello'],
            // A board position for goto, a character id for character-select.
            'value' => ['required_if:action,goto,character-select', 'nullable', 'integer', 'min:1', 'max:4294967295'],
            'pose' => ['required_if:action,character-pose', 'nullable', Rule::enum(CharacterPose::class)],
        ]);

        broadcast(new RemoteCommand($token, $data['action'], $data['value'] ?? null, $data['pose'] ?? null));

        return response()->noContent();
    }

    /** The presentation publishes its state for the remote control. */
    public function state(Request $request, string $token): Response
    {
        $data = $request->validate([
            'presenter_token' => ['required', 'string'],
            'state' => ['required', 'array'],
            'state.index' => ['required', 'integer', 'min:0'],
            'state.count' => ['required', 'integer', 'min:0'],
            'state.board' => ['nullable', 'array'],
            'state.board.position' => ['required_with:state.board', 'integer'],
            'state.board.title' => ['nullable', 'string', 'max:255'],
            'state.board.notes' => ['nullable', 'string', 'max:5000'],
            'state.board.day' => ['nullable', 'integer'],
            'state.board.time' => ['nullable', 'string', 'max:8'],
            'state.board.location' => ['nullable', 'string', 'max:255'],
            'state.characters' => ['present', 'array', 'max:50'],
            'state.characters.*.id' => ['required', 'integer'],
            'state.characters.*.name' => ['required', 'string', 'max:100'],
            'state.character_id' => ['nullable', 'integer'],
            'state.character_pose' => ['required', Rule::enum(CharacterPose::class)],
            'state.timer' => ['required', 'array'],
            'state.timer.remaining' => ['required', 'integer', 'min:0'],
            'state.timer.total' => ['required', 'integer', 'min:0'],
            'state.timer.running' => ['required', 'boolean'],
            'state.timer.nearEnd' => ['required', 'boolean'],
            'state.timer.timeOut' => ['required', 'boolean'],
        ]);

        abort_unless(RemoteSession::isPresenter($token, $data['presenter_token']), 403);

        broadcast(new RemoteState($token, $data['state']));

        return response()->noContent();
    }

    /** End a pairing (presenter only). */
    public function destroy(Request $request, string $token): Response
    {
        abort_unless(RemoteSession::isPresenter($token, $request->input('presenter_token')), 403);

        RemoteSession::forget($token);

        return response()->noContent();
    }
}
