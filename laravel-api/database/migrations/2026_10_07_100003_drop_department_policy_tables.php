<?php

declare(strict_types=1);

use App\Support\Database\ReplaysMigrations;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RFC-001 slice 4: departments and row-level policies are removed (REQ-001).
 * No code read admin_policy_view; the trigger only fed department_management_mst.
 */
return new class extends Migration
{
    use ReplaysMigrations;

    private const TABLES = [
        'department_mst_hist',
        'policy_department_mst_hist',
        'department_management_mst',
        'admin_department_mst',
        'policy_department_mst',
        'department_mst',
    ];

    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS admin_policy_view');
        DB::unprepared('DROP TRIGGER IF EXISTS after_policy_department_insert ON policy_department_mst');
        DB::unprepared('DROP FUNCTION IF EXISTS insert_into_department_management');

        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        $this->replay([
            '0001_01_01_000009_create_department_mst_table.php',
            '0001_01_01_000010_create_admin_department_mst_table.php',
            '0001_01_01_000011_create_policy_department_mst_table.php',
            '0001_01_01_000012_create_department_management_mst_table.php',
            '0001_01_01_000030_create_department_mst_hist_table.php',
            '0001_01_01_000031_create_policy_department_mst_hist_table.php',
            '0001_01_01_000047_create_after_policy_department_insert.php',
            '0001_01_01_000049_create_admin_policy_view.php',
        ]);
    }
};
