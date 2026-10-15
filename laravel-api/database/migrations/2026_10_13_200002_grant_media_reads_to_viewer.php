<?php

declare(strict_types=1);

use App\Constants\CommonVal;
use App\Enums\AdminRole;
use App\Support\Permissions\AdminRoutePermissions;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

/**
 * ADR-0014: the viewer role browses and downloads files like it reads every other screen.
 */
return new class extends Migration
{
    private const READS = ['media.list', 'media.download-url'];

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
