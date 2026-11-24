<?php

namespace Database\Seeders;

use App\Models\Board;
use App\Models\Journey;
use App\Models\User;
use Illuminate\Database\Seeder;

class JourneySeeder extends Seeder
{
    private const NOTES = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna';

    /** Demo journey: two days in 11 boards, with status bar and simulator data. */
    public function run(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'demo@a-nicer-way.test'],
            ['name' => 'Demo', 'password' => bcrypt(str()->random(32))],
        );

        $journey = Journey::query()->updateOrCreate(
            ['slug' => 'demo'],
            [
                'user_id' => $user->id,
                'title' => 'Demo Journey',
                'description' => 'Zwei Tage im Leben, erzählt in elf Boards.',
                'published_at' => now(),
            ],
        );

        foreach (['Kevin', 'Gruppe', 'Bus'] as $index => $name) {
            $journey->characters()->updateOrCreate(['position' => $index + 1], ['name' => $name]);
        }

        foreach ($this->boards() as $position => $board) {
            Board::query()->updateOrCreate(
                ['journey_id' => $journey->id, 'position' => $position],
                $board + ['title' => "Board $position", 'notes' => self::NOTES],
            );
        }
    }

    /** @return array<int, array<string, mixed>> keyed by position */
    private function boards(): array
    {
        $rows = [
            [1, '06:30', 'Zuhause', false, 'verschlafen', 'Wecker klingelt'],
            [1, '08:00', 'Zug nach Zürich', false, 'ruhig', 'Fahrplan'],
            [1, '09:30', 'Büro', false, 'konzentriert', 'Aufgabenliste'],
            [1, '12:15', 'Mittagspause', false, 'entspannt', 'Restaurant suchen'],
            [1, '15:00', 'Meeting', false, 'angespannt', 'Kalender'],
            [1, '22:00', 'Zuhause', true, 'müde', 'Tagesrückblick'],
            [2, '07:00', 'Zuhause', false, 'munter', 'Wetter'],
            [2, '10:00', 'Workshop', false, 'neugierig', 'Notizen'],
            [2, '13:30', 'Unterwegs', false, 'gelassen', 'Navigation'],
            [2, '19:00', 'Abendessen', false, 'zufrieden', 'Reservation'],
            [2, '23:30', 'Zuhause', true, 'erschöpft', 'Gute Nacht'],
        ];

        $boards = [];
        foreach ($rows as $index => [$day, $time, $location, $isNight, $mood, $screen]) {
            $boards[$index + 1] = [
                'day' => $day,
                'time' => $time,
                'location' => $location,
                'is_night' => $isNight,
                'mood' => $mood,
                'simulator_state' => ['screen' => $screen],
            ];
        }

        return $boards;
    }
}
