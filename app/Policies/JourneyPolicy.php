<?php

namespace App\Policies;

use App\Models\Journey;
use App\Models\User;

class JourneyPolicy
{
    /** Only the owner may edit a journey and its boards. */
    public function manage(User $user, Journey $journey): bool
    {
        return $user->id === $journey->user_id;
    }
}
