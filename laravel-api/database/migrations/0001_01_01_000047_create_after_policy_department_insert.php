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
        // Create the function
        DB::unprepared("
          CREATE OR REPLACE FUNCTION insert_into_department_management() RETURNS TRIGGER AS $$
            BEGIN
              -- Insert a new record into department_management_mst table
              INSERT INTO department_management_mst (department_mst_id, policy_department_mst_id, created_at, updated_at)
              SELECT id, NEW.id, now(), now() FROM department_mst WHERE name = 'root';
              RETURN NEW;
            END;
          $$ LANGUAGE plpgsql;
        ");

        // Create the trigger
        DB::unprepared('
          CREATE TRIGGER after_policy_department_insert
          AFTER INSERT ON policy_department_mst
          FOR EACH ROW
          EXECUTE FUNCTION insert_into_department_management();
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the trigger
        DB::unprepared('DROP TRIGGER IF EXISTS after_policy_department_insert ON policy_department_mst');

        // Drop the function
        DB::unprepared('DROP FUNCTION IF EXISTS insert_into_department_management');
    }
};
