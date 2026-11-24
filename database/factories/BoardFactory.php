<?php

namespace Database\Factories;

use App\Models\Journey;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Board> */
class BoardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'journey_id' => Journey::factory(),
            'position' => fake()->unique()->numberBetween(1, 10000),
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'notes' => fake()->paragraph(),
            'day' => 1,
            'time' => fake()->time('H:i'),
            'location' => fake()->city(),
            'is_night' => false,
            'mood' => null,
            'simulator_state' => null,
        ];
    }
}
