<?php

declare(strict_types=1);

namespace App\Support\Permissions;

use App\Constants\CommonVal;
use App\Constants\PermissionConst;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Turns every named admin route into a permission row (ADR-0013), so a new API becomes a permission
 * without code changes. Used by `permission:sync-routes` and the migration that moved the fixed roles.
 */
final class AdminRoutePermissions
{
    public function __construct(private readonly Router $router) {}

    /**
     * Permission names of the admin routes, with the HTTP method each one guards.
     *
     * @return array<string, string> name => method
     */
    public function all(): array
    {
        $permissions = [];
        foreach ($this->router->getRoutes()->getRoutes() as $route) {
            $name = $route->getName();
            if ($name === null || ! $this->isAdminRoute($route) || in_array($name, PermissionConst::ALWAYS_ALLOWED, true)) {
                continue;
            }
            $permissions[$name] = $route->methods()[0];
        }
        ksort($permissions);

        return $permissions;
    }

    /**
     * Admin routes without a name: AdminMiddleware refuses them, so they are bugs.
     *
     * @return list<string> URIs
     */
    public function unnamed(): array
    {
        $uris = [];
        foreach ($this->router->getRoutes()->getRoutes() as $route) {
            if ($route->getName() === null && $this->isAdminRoute($route)) {
                $uris[] = $route->uri();
            }
        }

        return $uris;
    }

    /**
     * Create the missing permissions; with $prune, delete the ones whose route is gone.
     *
     * @return array{added: list<string>, stale: list<string>}
     */
    public function sync(bool $prune = false): array
    {
        $names = array_keys($this->all());
        $existing = Permission::query()->where('guard_name', CommonVal::ADMIN_GUARD)->pluck('name')->all();

        $added = array_values(array_diff($names, $existing));
        foreach ($added as $name) {
            Permission::query()->create(['name' => $name, 'guard_name' => CommonVal::ADMIN_GUARD]);
        }

        $stale = array_values(array_diff($existing, $names));
        if ($prune && $stale !== []) {
            Permission::query()->where('guard_name', CommonVal::ADMIN_GUARD)->whereIn('name', $stale)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return ['added' => $added, 'stale' => $stale];
    }

    private function isAdminRoute(Route $route): bool
    {
        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && ($middleware === PermissionConst::ADMIN_MIDDLEWARE || str_starts_with($middleware, PermissionConst::ADMIN_MIDDLEWARE.':'))) {
                return true;
            }
        }

        return false;
    }
}
