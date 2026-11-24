<?php

namespace App\Policies;

use App\Models\Character;
use App\Models\User;

class CharacterPolicy
{
    public function manage(User $user, Character $character): bool
    {
        return $user->id === $character->journey->user_id;
    }
}
