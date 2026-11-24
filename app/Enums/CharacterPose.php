<?php

namespace App\Enums;

/** What a character is doing in the scene; each pose can have its own image. */
enum CharacterPose: string
{
    case Idle = 'idle';
    case Go = 'go';
    case Pause = 'pause';
    case Greet = 'greet';
}
