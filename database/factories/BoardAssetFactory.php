<?php

namespace Database\Factories;

use App\Enums\AssetLayer;
use App\Models\Board;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\BoardAsset> */
class BoardAssetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'board_id' => Board::factory(),
            'layer' => fake()->randomElement(AssetLayer::cases()),
            'path' => 'journeys/'.fake()->slug().'.png',
            'position' => 0,
        ];
    }
}
