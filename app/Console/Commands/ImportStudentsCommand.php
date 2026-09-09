<?php

namespace App\Console\Commands;

use App\Services\StudentCsvImporter;
use Illuminate\Console\Command;

class ImportStudentsCommand extends Command
{
    protected $signature = 'students:import {path : CSV path} {--school= : School ID}';
    protected $description = 'Import students and optional guardians from a CSV file';

    public function handle(StudentCsvImporter $importer): int
    {
        $path = $this->argument('path');
        $schoolId = (int) ($this->option('school') ?: $this->ask('School ID'));
        if (!is_file($path) || !$schoolId) {
            $this->error('Provide a valid CSV path and school ID.');
            return self::FAILURE;
        }
        $result = $importer->import($path, $schoolId);
        if (is_int($result)) {
            $this->info("Imported {$result} student rows.");
        } else {
            $this->table(['Metric', 'Value'], [
                ['Lignes analysées', $result['analyzed']], ['Créées', $result['created']],
                ['Ignorées', $result['ignored']], ['Erreurs', $result['errors']], ['Doublons', $result['duplicates']],
            ]);
        }
        return self::SUCCESS;
    }
}
