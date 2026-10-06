<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Management\EntryDescriptionMgmt;
use App\Models\Management\EntryMgmt;
use App\Observers\EntryDescriptionMgmtObserver;
use App\Observers\EntryMgmtObserver;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->resolving(Command::class, function ($command, $app) {
            $command->setLaravel($app);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fail fast on N+1 lazy loading and on mass-assigned attributes that would be dropped
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        // Register observers to clean up parent layout_structure when children are deleted
        EntryMgmt::observe(EntryMgmtObserver::class);
        EntryDescriptionMgmt::observe(EntryDescriptionMgmtObserver::class);
    }
}
