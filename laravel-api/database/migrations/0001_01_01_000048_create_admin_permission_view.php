<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
          CREATE VIEW admin_permission_view AS
            SELECT
              am.id         AS admin_mst_id,
              rm.id         AS role_mst_id,
              rm.name       AS role_name,
              CASE
                  WHEN am2.type = 0 THEN 'GET'
                  WHEN am2.type = 1 THEN 'POST'
                  WHEN am2.type = 2 THEN 'PUT'
                  WHEN am2.type = 3 THEN 'PATCH'
                  WHEN am2.type = 4 THEN 'DELETE'
                  ELSE null
              END AS type,
              am2.name      AS api_name,
              am2.path      AS path,
              fm.name       AS feature_name,
              fm.group_name AS feature_group
            FROM
              admin_mst am                                              -- Account
              INNER JOIN admin_role_mst arm ON arm.admin_mst_id = am.id 	  -- AdminMst role
              INNER JOIN role_mst rm ON rm.id = arm.role_mst_id             -- RoleMst
              INNER JOIN api_role_mst arm2 ON arm2.role_mst_id = rm.id		    -- ApiMst feature
              INNER JOIN api_mst am2 ON am2.id = arm2.api_mst_id 			      -- ApiMst id
              INNER JOIN feature_mst fm ON fm.id = am2.feature_mst_id 		  -- FeatureMst
            WHERE
              am.status = 1
              AND am.is_active = TRUE
              AND rm.is_active = TRUE
              AND am2.is_active = TRUE
              AND fm.status = 1
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS admin_permission_view');
    }
};
