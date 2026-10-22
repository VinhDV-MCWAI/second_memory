<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ADR-0022: articles count their views (a number only, never who read), "most saved" counts the end
 * users who saved an article, and articles and categories are searchable the way skills were
 * (ADR-0009): `search_text` is the accent-free title / name and summary for the typo fallback
 * (GiST trigram), `search_tsv` the weighted vector (A title / name, B summary, C body / description)
 * for full text (GIN).
 */
return new class extends Migration
{
    /** [table => [trigram text, [[part, weight], ...]]] */
    private const SEARCH = [
        'article' => [
            "title || ' ' || coalesce(summary, '')",
            [['title', 'A'], ["coalesce(summary, '')", 'B'], ['body', 'C']],
        ],
        'content_category' => [
            'name',
            [['name', 'A'], ["coalesce(description, '')", 'C']],
        ],
    ];

    public function up(): void
    {
        Schema::table('article', function (Blueprint $table) {
            $table->unsignedBigInteger('view_count')->default(0);
        });
        Schema::table('end_user_saved', function (Blueprint $table) {
            $table->index('article_id');
        });

        foreach (self::SEARCH as $table => [$text, $parts]) {
            $vector = implode(' || ', array_map(
                fn (array $part): string => "setweight(to_tsvector('simple', f_unaccent(lower({$part[0]}))), '{$part[1]}')",
                $parts,
            ));
            DB::statement("ALTER TABLE {$table} ADD COLUMN search_text text GENERATED ALWAYS AS (f_unaccent(lower({$text}))) STORED");
            DB::statement("ALTER TABLE {$table} ADD COLUMN search_tsv tsvector GENERATED ALWAYS AS ({$vector}) STORED");
            DB::statement("CREATE INDEX {$table}_search_tsv ON {$table} USING gin (search_tsv)");
            DB::statement("CREATE INDEX {$table}_search_trgm_gist ON {$table} USING gist (search_text gist_trgm_ops)");
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::SEARCH) as $table) {
            // Drops their indexes too
            DB::statement("ALTER TABLE {$table} DROP COLUMN search_tsv, DROP COLUMN search_text");
        }
        Schema::table('end_user_saved', function (Blueprint $table) {
            $table->dropIndex(['article_id']);
        });
        Schema::table('article', function (Blueprint $table) {
            $table->dropColumn('view_count');
        });
    }
};
