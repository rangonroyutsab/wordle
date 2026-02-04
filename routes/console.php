<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule the daily word generation to run at midnight
Schedule::command('wordle:set-daily-word --days=7')
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->runInBackground();
