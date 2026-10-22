<?php

declare(strict_types=1);

namespace App\Repositories\Content;

use App\Enums\ArticleSort;
use Illuminate\Support\Facades\DB;

/**
 * Numbers for the admin dashboard: one query per figure.
 */
final class DashboardRepository
{
    public function categories(): int
    {
        return DB::table('content_category')->count();
    }

    /**
     * @return array{articles: int, public_articles: int, views: int}
     */
    public function articleCounts(): array
    {
        $row = DB::table('article')
            ->selectRaw('count(*) AS articles, count(*) FILTER (WHERE is_public) AS public_articles, coalesce(sum(view_count), 0) AS views')
            ->first();

        return ['articles' => (int) $row?->articles, 'public_articles' => (int) $row?->public_articles, 'views' => (int) $row?->views];
    }

    /**
     * Articles ranked by views or saves, private ones too; articles nobody read or saved are left out.
     *
     * @return list<array{id: int, title: string, slug: string, is_public: bool, count: int}>
     */
    public function top(ArticleSort $sort, int $limit): array
    {
        return DB::table('article')
            ->select(['id', 'title', 'slug', 'is_public'])
            ->selectRaw($sort->expression().' AS count')
            ->whereRaw($sort->expression().' > 0')
            ->orderByDesc('count')
            ->orderBy('title')
            ->limit($limit)
            ->get()
            ->map(fn (object $row): array => [
                'id' => (int) $row->id,
                'title' => (string) $row->title,
                'slug' => (string) $row->slug,
                'is_public' => (bool) $row->is_public,
                'count' => (int) $row->count,
            ])
            ->all();
    }
}
