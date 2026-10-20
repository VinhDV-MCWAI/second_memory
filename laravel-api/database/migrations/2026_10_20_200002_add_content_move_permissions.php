<?php

declare(strict_types=1);

use App\Constants\CommonVal;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * The ↑ ↓ buttons of categories and articles (ADR-0022) are writes: the owner passes every check,
 * the viewer gets nothing new.
 */
return new class extends Migration
{
    private const PERMISSIONS = ['content-category.move', 'article.move'];

    public function up(): void
    {
        foreach (self::PERMISSIONS as $name) {
            Permission::findOrCreate($name, CommonVal::ADMIN_GUARD);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()->where('guard_name', CommonVal::ADMIN_GUARD)->whereIn('name', self::PERMISSIONS)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
