<?php

declare(strict_types=1);

use App\Constants\CommonVal;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * ADR-0022: Obsidian notes become articles. `external_key` is the note's path in the vault, set
 * only on imported articles; the import route is a permission like every admin route (ADR-0013).
 */
return new class extends Migration
{
    private const PERMISSION = 'article.import';

    public function up(): void
    {
        Schema::table('article', function (Blueprint $table) {
            $table->string('external_key', 500)->nullable()->unique();
        });

        Permission::findOrCreate(self::PERMISSION, CommonVal::ADMIN_GUARD);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()->where(['guard_name' => CommonVal::ADMIN_GUARD, 'name' => self::PERMISSION])->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Schema::table('article', function (Blueprint $table) {
            $table->dropUnique(['external_key']);
            $table->dropColumn('external_key');
        });
    }
};
