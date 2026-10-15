<?php

declare(strict_types=1);

use App\Constants\CommonVal;
use App\Enums\AdminRole;
use App\Support\Permissions\AdminRoutePermissions;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

/**
 * ADR-0014: anyone may read the status of their own background tasks; starting the test job is the owner's.
 */
return new class extends Migration
{
    private const READS = ['background-task.status'];

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
