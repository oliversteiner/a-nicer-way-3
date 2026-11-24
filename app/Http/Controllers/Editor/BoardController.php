<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Editor\BoardRequest;
use App\Models\Board;
use App\Models\Journey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class BoardController extends Controller
{
    public function store(BoardRequest $request, Journey $journey): RedirectResponse
    {
        Gate::authorize('manage', $journey);

        $journey->boards()->create($this->attributes($request) + [
            'position' => ($journey->boards()->max('position') ?? 0) + 1,
        ]);

        return back();
    }

    public function update(BoardRequest $request, Board $board): RedirectResponse
    {
        Gate::authorize('manage', $board);

        $board->update($this->attributes($request, $board));

        return back();
    }

    public function destroy(Board $board): RedirectResponse
    {
        Gate::authorize('manage', $board);

        DB::transaction(function () use ($board) {
            $board->delete();
            $board->journey->resequenceBoards();
        });

        return back();
    }

    public function move(Request $request, Board $board): RedirectResponse
    {
        Gate::authorize('manage', $board);

        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];

        $neighbour = $board->journey->boards()
            ->where('position', $direction === 'up' ? '<' : '>', $board->position)
            ->reorder('position', $direction === 'up' ? 'desc' : 'asc')
            ->first();

        if ($neighbour) {
            DB::transaction(function () use ($board, $neighbour) {
                [$a, $b] = [$board->position, $neighbour->position];
                // The unique (journey_id, position) index forbids a direct swap.
                $board->update(['position' => 0]);
                $neighbour->update(['position' => $a]);
                $board->update(['position' => $b]);
            });
        }

        return back();
    }

    /** @return array<string, mixed> */
    private function attributes(BoardRequest $request, ?Board $board = null): array
    {
        $data = $request->validated();
        $screen = $data['simulator_screen'] ?? null;
        unset($data['simulator_screen']);

        $state = $board?->simulator_state ?? [];
        if ($screen === null || $screen === '') {
            unset($state['screen']);
        } else {
            $state['screen'] = $screen;
        }
        $data['simulator_state'] = $state === [] ? null : $state;

        return $data;
    }
}
