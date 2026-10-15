<?php

namespace App\Console\Commands;

use App\ReviewData;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;

class SyncGoogleReviewsCommand extends Command
{
    protected $signature = 'app:sync-google-reviews';

    protected $description = 'Synchronise les avis Google depuis Places en cache local pour un rafraîchissement quotidien.';

    public function handle(ReviewData $reviewData): int
    {
        $reviews = $reviewData->googleReviews();

        $this->info(sprintf('Avis Google synchronisés : %d', count($reviews)));

        return self::SUCCESS;
    }

    public function schedule(Schedule $schedule): void
    {
        $schedule->command(static::class)
            ->dailyAt('02:00')
            ->withoutOverlapping();
    }
}
