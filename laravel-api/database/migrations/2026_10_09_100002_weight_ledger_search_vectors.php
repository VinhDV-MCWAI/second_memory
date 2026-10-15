<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * BUG-04 (ADR-0009 amendment): "best match first" means a title match outranks a summary match.
 * `search_tsv` gets weights (A title / name, B summary / category, C description), which ts_rank
 * applies. PostgreSQL 16 cannot change a generated column's expression, so the column (and its GIN
 * index) is recreated. `search_text` and the trigram fallback are unchanged.
 */
return new class extends Migration
{
    /** Weighted vector per table: [text, weight] parts. */
    private const WEIGHTED = [
        'skill' => [['name', 'A'], ['category', 'B'], ["coalesce(description, '')", 'C']],
        'evidence' => [['title', 'A'], ["coalesce(summary, '')", 'B']],
    ];

    /** Unweighted vectors of 2026_10_08_100005, for down(). */
    private const PLAIN = [
        'skill' => "name || ' ' || category || ' ' || coalesce(description, '')",
        'evidence' => "title || ' ' || coalesce(summary, '')",
    ];

    public function up(): void
    {
        foreach (self::WEIGHTED as $table => $parts) {
            $vector = implode(' || ', array_map(
                fn (array $part): string => "setweight(to_tsvector('simple', f_unaccent(lower({$part[0]}))), '{$part[1]}')",
                $parts,
            ));
            $this->replaceVector($table, $vector);
        }
    }

    public function down(): void
    {
        foreach (self::PLAIN as $table => $text) {
            $this->replaceVector($table, "to_tsvector('simple', f_unaccent(lower({$text})))");
        }
    }

    private function replaceVector(string $table, string $vector): void
    {
        // Drops its GIN index too
        DB::statement("ALTER TABLE {$table} DROP COLUMN search_tsv");
        DB::statement("ALTER TABLE {$table} ADD COLUMN search_tsv tsvector GENERATED ALWAYS AS ({$vector}) STORED");
        DB::statement("CREATE INDEX {$table}_search_tsv ON {$table} USING gin (search_tsv)");
    }
};
