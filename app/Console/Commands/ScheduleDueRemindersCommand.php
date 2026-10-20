<?php

namespace App\Console\Commands;

use App\Services\DueReminderService;
use Illuminate\Console\Command;

class ScheduleDueRemindersCommand extends Command
{
    protected $signature = 'due-reminders:schedule {--date= : ISO date to schedule, defaults to today}';
    protected $description = 'Schedule idempotent WhatsApp due-date reminders';

    public function handle(DueReminderService $service): int
    {
        $date = $this->option('date');
        $count = $service->schedule($date ? new \DateTimeImmutable($date) : null);
        $this->info("Scheduled {$count} due reminders.");
        return self::SUCCESS;
    }
}
