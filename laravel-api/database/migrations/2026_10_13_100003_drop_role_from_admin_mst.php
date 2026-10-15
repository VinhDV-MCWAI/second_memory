<?php

declare(strict_types=1);

use App\Enums\AdminRole;
use App\Models\Master\AdminMst;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ADR-0013, contract step: roles are assigned through `model_has_roles` only, so the old
 * `admin_mst.role` column goes. down() refills it: owner when the admin holds the owner role.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_mst', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('admin_mst', function (Blueprint $table) {
            $table->string('role', 20)->default(AdminRole::VIEWER->value)->after('is_active')->comment('owner | viewer');
        });

        DB::table('admin_mst')
            ->whereIn('id', DB::table('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('roles.name', AdminRole::OWNER->value)
                ->where('model_has_roles.model_type', (new AdminMst)->getMorphClass())
                ->select('model_has_roles.model_id'))
            ->update(['role' => AdminRole::OWNER->value]);
    }
};
