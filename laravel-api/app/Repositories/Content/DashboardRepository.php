<?php

declare(strict_types=1);

namespace App\Repositories\Content;

use Illuminate\Support\Facades\DB;

/**
 * Counts for the admin dashboard: one query per table.
 */
final class DashboardRepository
{
    public function categories(): int
    {
        return DB::table('content_category')->count();
    }

    /**
     * @return array{articles: int, public_articles: int}
     */
    public function articleCounts(): array
    {
        $row = DB::table('article')->selectRaw('count(*) AS articles, count(*) FILTER (WHERE is_public) AS public_articles')->first();

        return ['articles' => (int) $row?->articles, 'public_articles' => (int) $row?->public_articles];
    }
}
