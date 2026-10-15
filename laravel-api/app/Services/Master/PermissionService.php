<?php

declare(strict_types=1);

namespace App\Services\Master;

use App\Constants\CommonVal;
use App\Constants\PermissionConst;
use App\Support\Permissions\AdminRoutePermissions;
use Spatie\Permission\Models\Permission;

/**
 * Every permission, grouped by feature for the permission matrix (ADR-0013).
 */
class PermissionService
{
    public function __construct(private readonly AdminRoutePermissions $routes) {}

    /**
     * @return list<array{feature: string, permissions: list<array{name: string, action: string, method: string|null}>}>
     */
    public function grouped(): array
    {
        $methods = $this->routes->all();
        $groups = [];
        foreach (Permission::query()->where('guard_name', CommonVal::ADMIN_GUARD)->orderBy('name')->pluck('name') as $name) {
            [$feature, $action] = array_pad(explode(PermissionConst::SEPARATOR, (string) $name, 2), 2, '');
            $groups[$feature][] = [
                'name' => (string) $name,
                'action' => $action,
                // null: the route is gone, `permission:sync-routes --prune` removes it
                'method' => $methods[$name] ?? null,
            ];
        }

        return array_map(
            fn (string $feature, array $permissions): array => ['feature' => $feature, 'permissions' => $permissions],
            array_keys($groups),
            array_values($groups),
        );
    }
}
