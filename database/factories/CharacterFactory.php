<?php

namespace Database\Factories;

use App\Models\Journey;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Character> */
class CharacterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'journey_id' => Journey::factory(),
            'position' => fake()->unique()->numberBetween(1, 10000),
            'name' => fake()->firstName(),
        ];
    }
}
