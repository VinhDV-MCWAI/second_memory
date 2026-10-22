<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Constants\ContentConst;
use App\Enums\ArticleSort;
use App\Repositories\Content\DashboardRepository;

/**
 * Numbers for the admin dashboard.
 */
final class DashboardService
{
    public function __construct(private readonly DashboardRepository $dashboard) {}

    /**
     * @return array{categories: int, articles: int, public_articles: int, views: int, most_read: list<array{id: int, title: string, slug: string, is_public: bool, count: int}>, most_saved: list<array{id: int, title: string, slug: string, is_public: bool, count: int}>}
     */
    public function summary(): array
    {
        return [
            'categories' => $this->dashboard->categories(),
            ...$this->dashboard->articleCounts(),
            'most_read' => $this->dashboard->top(ArticleSort::MOST_READ, ContentConst::DASHBOARD_TOP),
            'most_saved' => $this->dashboard->top(ArticleSort::MOST_SAVED, ContentConst::DASHBOARD_TOP),
        ];
    }
}
