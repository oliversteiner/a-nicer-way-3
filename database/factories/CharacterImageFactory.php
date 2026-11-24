<?php

namespace Database\Factories;

use App\Enums\CharacterPose;
use App\Models\Character;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\CharacterImage> */
class CharacterImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'character_id' => Character::factory(),
            'pose' => CharacterPose::Idle,
            'path' => 'journeys/characters/'.fake()->slug().'.png',
        ];
    }
}
