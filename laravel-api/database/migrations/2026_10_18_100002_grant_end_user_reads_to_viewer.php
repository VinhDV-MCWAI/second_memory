<?php

declare(strict_types=1);

use App\Constants\CommonVal;
use App\Enums\AdminRole;
use App\Support\Permissions\AdminRoutePermissions;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

/**
 * The viewer role holds every read (ADR-0013): it sees end users and the registration switch.
 */
return new class extends Migration
{
    private const READS = ['end-user.list', 'end-user.registration'];

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
