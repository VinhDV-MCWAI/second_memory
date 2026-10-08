<?php

declare(strict_types=1);

namespace App\Providers;

use App\Constants\CommonVal;
use App\Constants\LedgerConst;
use App\Enums\AdminRole;
use App\Models\Master\AdminMst;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Command;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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

        // A deleted or disabled admin cannot log in, and an existing session stops working on the next request
        Auth::provider('active-admins', fn (Application $app, array $config): EloquentUserProvider => (new EloquentUserProvider($app['hash'], $config['model']))
            ->withQuery(fn (Builder $query) => $query->where('is_delete', false)->where('is_active', true)));

        // Roles (ADR-0005): only an owner may change data; checked by AdminMiddleware for non-read requests
        Gate::define(CommonVal::GATE_WRITE, fn (AdminMst $admin): bool => $admin->role === AdminRole::OWNER);

        RateLimiter::for('public', fn (Request $request): Limit => Limit::perMinute(LedgerConst::PUBLIC_RATE_PER_MINUTE)->by((string) $request->ip()));

        // Under /api so nginx routes it to Laravel (/docs belongs to the docs site)
        Scramble::configure()
            ->expose(ui: 'api/openapi', document: 'api/openapi.json')
            ->withDocumentTransformers(function (OpenApi $openApi): void {
                $openApi->secure(SecurityScheme::apiKey('cookie', config('session.cookie')));
            });
    }
}
