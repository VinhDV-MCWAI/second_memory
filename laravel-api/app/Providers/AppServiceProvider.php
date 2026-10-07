<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Management\EntryDescriptionMgmt;
use App\Models\Management\EntryMgmt;
use App\Observers\EntryDescriptionMgmtObserver;
use App\Observers\EntryMgmtObserver;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
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

        // Under /api so nginx routes it to Laravel (/docs belongs to the docs site, /api/docs to the public docs API)
        Scramble::configure()
            ->expose(ui: 'api/openapi', document: 'api/openapi.json')
            ->withDocumentTransformers(function (OpenApi $openApi): void {
                $openApi->secure(SecurityScheme::apiKey('cookie', 'access_token'));
            });

        // Register observers to clean up parent layout_structure when children are deleted
        EntryMgmt::observe(EntryMgmtObserver::class);
        EntryDescriptionMgmt::observe(EntryDescriptionMgmtObserver::class);
    }
}
