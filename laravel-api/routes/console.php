<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule cleanup of stuck uploads daily at 2 AM
Schedule::command('media:cleanup-stuck-uploads')
    ->dailyAt('02:00')
    ->runInBackground()
    ->withoutOverlapping()
    ->onSuccess(function () {
        info('Stuck uploads cleanup completed successfully');
    })
    ->onFailure(function () {
        logger()->error('Stuck uploads cleanup failed');
    });
