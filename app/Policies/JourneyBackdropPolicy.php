<?php

namespace App\Policies;

use App\Models\JourneyBackdrop;
use App\Models\User;

class JourneyBackdropPolicy
{
    public function manage(User $user, JourneyBackdrop $backdrop): bool
    {
        return $user->id === $backdrop->journey->user_id;
    }
}
