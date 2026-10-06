<?php

namespace App\Providers;

use App\Models\Management\EntryDescriptionMgmt;
use App\Models\Management\EntryMgmt;
use App\Observers\EntryDescriptionMgmtObserver;
use App\Observers\EntryMgmtObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->resolving(\Illuminate\Console\Command::class, function ($command, $app) {
            $command->setLaravel($app);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register observers to clean up parent layout_structure when children are deleted
        EntryMgmt::observe(EntryMgmtObserver::class);
        EntryDescriptionMgmt::observe(EntryDescriptionMgmtObserver::class);
    }
}
