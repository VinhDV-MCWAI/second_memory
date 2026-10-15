<?php

declare(strict_types=1);

use App\Constants\CommonVal;
use App\Enums\AdminRole;
use App\Models\Master\AdminMst;
use App\Support\Permissions\AdminRoutePermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * ADR-0013: the two fixed roles become rows. Every admin route becomes a permission; the viewer
 * role gets every read (GET) permission, the owner role needs none (it passes every check).
 * Each admin gets the role named by its `role` column, which stays until the admin API assigns roles.
 */
return new class extends Migration
{
    public function up(): void
    {
        $permissions = app(AdminRoutePermissions::class);
        $permissions->sync();

        foreach (AdminRole::cases() as $case) {
            Role::findOrCreate($case->value, CommonVal::ADMIN_GUARD);
        }
        $reads = array_keys(array_filter($permissions->all(), fn (string $method): bool => $method === 'GET'));
        Role::findByName(AdminRole::VIEWER->value, CommonVal::ADMIN_GUARD)->syncPermissions($reads);

        $roleIds = DB::table('roles')->where('guard_name', CommonVal::ADMIN_GUARD)->pluck('id', 'name');
        $morphClass = (new AdminMst)->getMorphClass();
        DB::table('admin_mst')->select(['id', 'role'])->orderBy('id')->each(function (object $admin) use ($roleIds, $morphClass): void {
            DB::table('model_has_roles')->insertOrIgnore([
                'role_id' => $roleIds[$admin->role],
                'model_type' => $morphClass,
                'model_id' => $admin->id,
            ]);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('model_has_roles')->delete();
        DB::table('role_has_permissions')->delete();
        DB::table('roles')->delete();
        DB::table('permissions')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
