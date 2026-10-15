<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Search per ADR-0009: full-text on unaccented lower-case text, trigram index for the typo fallback.
 * `f_unaccent` is the only SQL function in the schema; it normalizes text and holds no business rule.
 * Both extensions are trusted, so the database owner can create them without superuser.
 */
return new class extends Migration
{
    /** Searchable text per table; the generated column keeps the index right for every writer. */
    private const SEARCH_TEXT = [
        'skill' => "name || ' ' || category || ' ' || coalesce(description, '')",
        'evidence' => "title || ' ' || coalesce(summary, '')",
    ];

    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION f_unaccent(text) RETURNS text
                LANGUAGE sql IMMUTABLE PARALLEL SAFE STRICT
                AS $$ SELECT public.unaccent('public.unaccent'::regdictionary, $1) $$
            SQL);

        foreach (self::SEARCH_TEXT as $table => $text) {
            DB::statement("ALTER TABLE {$table} ADD COLUMN search_text text GENERATED ALWAYS AS (f_unaccent(lower({$text}))) STORED");
            DB::statement("CREATE INDEX {$table}_search_fts ON {$table} USING gin (to_tsvector('simple', search_text))");
            DB::statement("CREATE INDEX {$table}_search_trgm ON {$table} USING gin (search_text gin_trgm_ops)");
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::SEARCH_TEXT) as $table) {
            DB::statement("ALTER TABLE {$table} DROP COLUMN IF EXISTS search_text");
        }
        DB::statement('DROP FUNCTION IF EXISTS f_unaccent(text)');
        // The extensions stay: other objects may use them and dropping needs no rollback here
    }
};
