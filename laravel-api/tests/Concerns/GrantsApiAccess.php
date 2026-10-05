<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Enums\TypeOfMethod;
use App\Models\Master\AdminMst;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Models\Master\RoleMst;
use Illuminate\Support\Facades\DB;

/**
 * Grants a role access to an API route through api_mst/api_role_mst, which
 * feed admin_permission_view (the source of the Redis permission cache).
 */
trait GrantsApiAccess
{
    /**
     * Give the admin a role allowed to call the given routes, log in and
     * return the auth cookies.
     *
     * @param  array<int, array{0: string, 1: string}>  $routes  [method, route uri]
     * @return array<string, string>
     */
    protected function loginWithAccess(AdminMst $admin, array $routes): array
    {
        $role = RoleMst::firstOrCreate(
            ['name' => 'root'],
            ['permission' => '{}', 'is_active' => 1, 'is_delete' => 0],
        );

        foreach ($routes as [$method, $routeUri]) {
            $this->grantAccessTo($role, $method, $routeUri);
        }

        DB::table('admin_role_mst')->insertOrIgnore([
            'admin_mst_id' => $admin->id,
            'role_mst_id' => $role->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/admin/credential/login', [
            'user_name' => $admin->user_name,
            'password' => 'password',
        ]);

        $cookies = [];
        foreach ($response->headers->getCookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }

        return $cookies;
    }

    protected function grantAccessTo(RoleMst $role, string $method, string $routeUri): void
    {
        $feature = FeatureMst::firstOrCreate(
            ['name' => 'System Features'],
            ['group_name' => 'System', 'status' => 1, 'is_delete' => 0],
        );

        $api = ApiMst::firstOrCreate(
            ['path' => trim($routeUri, '/'), 'type' => constant(TypeOfMethod::class.'::'.strtoupper($method))->value],
            ['name' => substr("{$method} {$routeUri}", 0, 50), 'is_active' => 1, 'feature_mst_id' => $feature->id, 'is_delete' => 0],
        );

        DB::table('api_role_mst')->insertOrIgnore([
            'api_mst_id' => $api->id,
            'role_mst_id' => $role->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
