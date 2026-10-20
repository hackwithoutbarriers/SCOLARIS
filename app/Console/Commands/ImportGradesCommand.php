<?php

namespace App\Console\Commands;

use App\Services\GradeCsvImporter;
use Illuminate\Console\Command;
use Throwable;

class ImportGradesCommand extends Command
{
    protected $signature = 'grades:import {path : CSV path} {--school= : School ID}';
    protected $description = 'Import validated assessment grades from a CSV file';

    public function handle(GradeCsvImporter $importer): int
    {
        $schoolId = (int) ($this->option('school') ?: $this->ask('School ID'));
        try {
            $result = $importer->import($this->argument('path'), $schoolId);
            $this->table(['Metric', 'Value'], [
                ['Analyzed', $result['analyzed']],
                ['Created', $result['created']],
                ['Updated', $result['updated']],
                ['Errors', $result['errors']],
            ]);
            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }
}
