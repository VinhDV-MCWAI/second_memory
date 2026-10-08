<?php

declare(strict_types=1);

use App\Enums\AdminRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RFC-001 slice 8, expand step (ADR-0005): one role column replaces the role → API chain.
 * Admins that held the `root` role become owners; if none did, the oldest active admin does.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_mst', function (Blueprint $table) {
            $table->string('role', 20)->default(AdminRole::VIEWER->value)->after('is_active')->comment('owner | viewer');
        });
        Schema::table('admin_mst_hist', function (Blueprint $table) {
            $table->string('role', 20)->nullable()->after('is_active')->comment('owner | viewer');
        });

        DB::table('admin_mst')
            ->whereIn('id', DB::table('admin_role_mst')
                ->join('role_mst', 'role_mst.id', '=', 'admin_role_mst.role_mst_id')
                ->where('role_mst.name', 'root')
                ->select('admin_role_mst.admin_mst_id'))
            ->update(['role' => AdminRole::OWNER->value]);

        if (! DB::table('admin_mst')->where('role', AdminRole::OWNER->value)->exists()) {
            $oldest = DB::table('admin_mst')->where('is_delete', false)->where('is_active', true)->orderBy('id')->value('id');
            if ($oldest !== null) {
                DB::table('admin_mst')->where('id', $oldest)->update(['role' => AdminRole::OWNER->value]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('admin_mst_hist', function (Blueprint $table) {
            $table->dropColumn('role');
        });
        Schema::table('admin_mst', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
