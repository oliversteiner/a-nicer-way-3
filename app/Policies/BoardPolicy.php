<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\User;

class BoardPolicy
{
    public function manage(User $user, Board $board): bool
    {
        return $user->id === $board->journey->user_id;
    }
}
