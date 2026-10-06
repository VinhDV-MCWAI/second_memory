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
        // Create the function
        DB::unprepared("
          CREATE OR REPLACE FUNCTION insert_into_api_role_from_api() RETURNS TRIGGER AS $$
            BEGIN
              -- Insert a new record into api_role_mst table
              INSERT INTO api_role_mst (api_mst_id, role_mst_id, created_at, updated_at)
              SELECT NEW.id, id, now(), now() FROM role_mst WHERE name = 'root';
              RETURN NEW;
            END;
          $$ LANGUAGE plpgsql;
        ");

        // Create the trigger
        DB::unprepared('
          CREATE TRIGGER after_api_insert AFTER INSERT ON api_mst
          FOR EACH ROW
          EXECUTE FUNCTION insert_into_api_role_from_api();
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the trigger
        DB::unprepared('DROP TRIGGER IF EXISTS after_api_insert ON api_mst');

        // Drop the function
        DB::unprepared('DROP FUNCTION IF EXISTS insert_into_api_role_from_api');
    }
};
