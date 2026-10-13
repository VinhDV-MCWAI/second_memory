<?php

declare(strict_types=1);

use App\Constants\CommonVal;
use App\Enums\AdminRole;
use App\Support\Permissions\AdminRoutePermissions;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

/**
 * ADR-0013: the role management API is new; the viewer role reads it like every other screen.
 */
return new class extends Migration
{
    private const READS = ['role.list', 'permission.list'];

    public function up(): void
    {
        app(AdminRoutePermissions::class)->sync();
        Role::findByName(AdminRole::VIEWER->value, CommonVal::ADMIN_GUARD)->givePermissionTo(self::READS);
    }

    public function down(): void
    {
        $viewer = Role::findByName(AdminRole::VIEWER->value, CommonVal::ADMIN_GUARD);
        foreach (self::READS as $permission) {
            $viewer->revokePermissionTo($permission);
        }
    }
};
