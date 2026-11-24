<?php

namespace App\Enums;

/** Parallax layer of a board asset: back moves slowest, front fastest. */
enum AssetLayer: string
{
    case Back = 'back';
    case Middle = 'middle';
    case Front = 'front';
}
