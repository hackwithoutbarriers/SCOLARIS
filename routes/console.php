<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('collections:schedule')->dailyAt('05:00');
Schedule::command('due-reminders:schedule')->dailyAt('05:05');
Schedule::command('student-imports:purge')->dailyAt('05:10');
Schedule::command('notifications:dispatch')->everyMinute();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
