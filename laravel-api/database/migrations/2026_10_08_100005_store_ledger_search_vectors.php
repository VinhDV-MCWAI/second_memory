<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Search storage amendment of ADR-0009 (API-03, DB-01 report):
 * - full text ranks on a stored `search_tsv` instead of recomputing to_tsvector for every match;
 * - the trigram index becomes GiST, which returns rows in `<<->` distance order (KNN), so the
 *   typo fallback stops after LIMIT rows; the planner never chose the GIN trigram index.
 */
return new class extends Migration
{
    /** Same text as `search_text` (2026_10_08_100003): a generated column cannot read another one. */
    private const SEARCH_TEXT = [
        'skill' => "name || ' ' || category || ' ' || coalesce(description, '')",
        'evidence' => "title || ' ' || coalesce(summary, '')",
    ];

    public function up(): void
    {
        foreach (self::SEARCH_TEXT as $table => $text) {
            DB::statement("ALTER TABLE {$table} ADD COLUMN search_tsv tsvector GENERATED ALWAYS AS (to_tsvector('simple', f_unaccent(lower({$text})))) STORED");
            DB::statement("CREATE INDEX {$table}_search_tsv ON {$table} USING gin (search_tsv)");
            DB::statement("DROP INDEX {$table}_search_fts");
            DB::statement("CREATE INDEX {$table}_search_trgm_gist ON {$table} USING gist (search_text gist_trgm_ops)");
            DB::statement("DROP INDEX {$table}_search_trgm");
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::SEARCH_TEXT) as $table) {
            DB::statement("CREATE INDEX {$table}_search_fts ON {$table} USING gin (to_tsvector('simple', search_text))");
            DB::statement("CREATE INDEX {$table}_search_trgm ON {$table} USING gin (search_text gin_trgm_ops)");
            DB::statement("DROP INDEX {$table}_search_trgm_gist");
            // Drops its GIN index too
            DB::statement("ALTER TABLE {$table} DROP COLUMN search_tsv");
        }
    }
};
