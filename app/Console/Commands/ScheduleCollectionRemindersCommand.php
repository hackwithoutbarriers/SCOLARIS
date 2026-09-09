<?php

namespace App\Console\Commands;

use App\Services\Payments\CollectionService;
use Illuminate\Console\Command;

class ScheduleCollectionRemindersCommand extends Command
{
    protected $signature = 'collections:schedule {--date= : ISO date to schedule, defaults to today}';

    protected $description = 'Schedule idempotent financial collection reminders';

    public function handle(CollectionService $service): int
    {
        $date = $this->option('date');
        $scheduled = $service->schedule($date ? new \DateTimeImmutable($date) : null);

        $this->info("Scheduled {$scheduled} collection reminders.");

        return self::SUCCESS;
    }
}
