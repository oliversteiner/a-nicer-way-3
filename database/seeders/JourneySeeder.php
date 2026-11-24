<?php

namespace Database\Seeders;

use App\Models\Board;
use App\Models\Journey;
use App\Models\User;
use Illuminate\Database\Seeder;

class JourneySeeder extends Seeder
{
    /** Demo journey with the 11 placeholder boards of the former API. */
    public function run(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'demo@a-nicer-way.test'],
            ['name' => 'Demo', 'password' => bcrypt(str()->random(32))],
        );

        $journey = Journey::query()->updateOrCreate(
            ['slug' => 'demo'],
            ['user_id' => $user->id, 'title' => 'Demo Journey', 'published_at' => now()],
        );

        foreach (range(1, 11) as $position) {
            Board::query()->updateOrCreate(
                ['journey_id' => $journey->id, 'position' => $position],
                [
                    'title' => "Board $position",
                    'notes' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna',
                    'day' => $position <= 6 ? 1 : 2,
                ],
            );
        }
    }
}
