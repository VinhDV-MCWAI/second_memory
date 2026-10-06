<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('
          CREATE VIEW admin_policy_view AS
          SELECT
            am.id          AS admin_mst_id,
            dm.code        AS department_code,
            dm.name        AS department_name,
            pdm.table_name AS table_name,
            pdm.row_id     AS row_id
          FROM
            admin_mst am                                                                -- Account
            INNER JOIN admin_department_mst adm ON adm.admin_mst_id = am.id                 --
            INNER JOIN department_mst dm ON dm.id = adm.department_mst_id                   -- DepartmentMst
            INNER JOIN department_management_mst dmm ON dmm.department_mst_id = dm.id       --
            INNER JOIN policy_department_mst pdm ON pdm.id = dmm.policy_department_mst_id   -- Policy
          WHERE
            am.status = 1
            AND dm.status = 1
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS admin_policy_view');
    }
};
