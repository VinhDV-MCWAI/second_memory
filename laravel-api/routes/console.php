<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Run by the ml-scheduler container (`schedule:work`); `make workers-restart` after changing it.
Schedule::command('media:clean-stuck-uploads')->hourly()->withoutOverlapping();
