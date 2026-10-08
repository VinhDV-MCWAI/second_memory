<?php

declare(strict_types=1);

use App\Support\Database\ReplaysMigrations;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RFC-001 slice 8, contract step (ADR-0005): the role → feature → API chain, its trigger and view,
 * the refresh-token table (unused since ADR-0004) and the old login failure counter go.
 */
return new class extends Migration
{
    use ReplaysMigrations;

    private const TABLES = [
        'api_role_mst',
        'admin_role_mst',
        'api_mst_hist',
        'feature_mst_hist',
        'role_mst_hist',
        'api_mst',
        'feature_mst',
        'role_mst',
        'token_mst',
    ];

    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS admin_permission_view');
        DB::unprepared('DROP TRIGGER IF EXISTS after_api_insert ON api_mst');
        DB::unprepared('DROP FUNCTION IF EXISTS insert_into_api_role_from_api');

        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        foreach (['admin_mst', 'admin_mst_hist'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('limit_access');
            });
        }
    }

    public function down(): void
    {
        foreach (['admin_mst', 'admin_mst_hist'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->integer('limit_access')->default(0)->comment('Limit access when login fails 5 times');
            });
        }

        $this->replay([
            '0001_01_01_000004_create_role_mst_table.php',
            '0001_01_01_000005_create_admin_role_mst_table.php',
            '0001_01_01_000006_create_feature_mst_table.php',
            '0001_01_01_000007_create_api_mst_table.php',
            '0001_01_01_000008_create_api_role_mst_table.php',
            '0001_01_01_000018_create_token_mst_table.php',
            '0001_01_01_000032_create_role_mst_hist_table.php',
            '0001_01_01_000033_create_feature_mst_hist_table.php',
            '0001_01_01_000034_create_api_mst_hist_table.php',
            '0001_01_01_000046_create_after_api_insert.php',
            '0001_01_01_000048_create_admin_permission_view.php',
        ]);
    }
};
