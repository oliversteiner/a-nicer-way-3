<?php

namespace Database\Factories;

use App\Enums\AssetLayer;
use App\Enums\BackdropAlign;
use App\Models\Journey;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\JourneyBackdrop> */
class JourneyBackdropFactory extends Factory
{
    public function definition(): array
    {
        return [
            'journey_id' => Journey::factory(),
            'layer' => AssetLayer::Back,
            'path' => 'journeys/backdrops/'.fake()->slug().'.png',
            'position' => 0,
            'speed' => 0.3,
            'align' => BackdropAlign::Bottom,
            'height' => 100,
            'repeat' => true,
        ];
    }
}
