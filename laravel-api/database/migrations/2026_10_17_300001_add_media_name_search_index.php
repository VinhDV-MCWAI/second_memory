<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Admin search over file and folder names (SEARCH-01). One GiST trigram index on the unaccented
 * lower-case name of live rows serves both steps of the search: `LIKE '%word%'` for the exact match
 * and `<%` / `<<->` (in distance order, stopping after LIMIT rows) for the typo fallback. No new column.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE INDEX media_mgmt_name_trgm ON media_mgmt USING gist ((f_unaccent(lower(original_name))) gist_trgm_ops) WHERE is_delete = false');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS media_mgmt_name_trgm');
    }
};
