<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\LegacyJourneyImporter;
use Illuminate\Console\Command;
use InvalidArgumentException;

class ImportLegacyJourney extends Command
{
    protected $signature = 'journey:import-legacy
        {path : Folder of the old v2 website (contains data/defaultdata.json and images/)}
        {--user= : E-mail of the user who owns the journey}
        {--slug=a-nicer-way : Slug of the journey}
        {--title= : Title of the journey (default: A Nicer Way)}
        {--publish : Publish the journey right away}
        {--replace : Replace an earlier import with the same slug}';

    protected $description = 'Import the journey of the old v2 website: boards, images, characters and backdrops';

    public function handle(LegacyJourneyImporter $importer): int
    {
        $email = (string) $this->option('user');
        if ($email === '') {
            $this->error('Please say who owns the journey: --user=<e-mail>');

            return self::FAILURE;
        }

        $owner = User::query()->where('email', $email)->first();
        if (! $owner) {
            $this->error("No user with the e-mail {$email}.");

            return self::FAILURE;
        }

        try {
            $result = $importer->import(
                (string) $this->argument('path'),
                $owner,
                (string) $this->option('slug'),
                (string) ($this->option('title') ?: 'A Nicer Way'),
                (bool) $this->option('publish'),
                (bool) $this->option('replace'),
            );
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Imported «{$result['journey']->title}» ({$result['journey']->slug}) for {$owner->email}.");
        $this->table(['Part', 'Count'], [
            ['Boards', $result['boards']],
            ['Board images', $result['assets']],
            ['Characters', $result['characters']],
            ['Character images', $result['images']],
            ['Backdrops', $result['backdrops']],
        ]);

        if ($result['missing'] !== []) {
            $this->warn('Files not found (skipped): '.implode(', ', $result['missing']));
        }

        if (! $result['journey']->published_at) {
            $this->line('The journey is a draft. Publish it in the editor or import with --publish.');
        }

        return self::SUCCESS;
    }
}
