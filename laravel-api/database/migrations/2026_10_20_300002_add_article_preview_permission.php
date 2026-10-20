<?php

declare(strict_types=1);

use App\Constants\CommonVal;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * The editor's Preview tab (ADR-0022) is a POST: the owner passes every check, the viewer, who
 * cannot edit, gets nothing new.
 */
return new class extends Migration
{
    private const PERMISSION = 'article.preview';

    public function up(): void
    {
        Permission::findOrCreate(self::PERMISSION, CommonVal::ADMIN_GUARD);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()->where(['guard_name' => CommonVal::ADMIN_GUARD, 'name' => self::PERMISSION])->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
