<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Repositories\Content\DashboardRepository;

/**
 * Numbers for the admin dashboard.
 */
final class DashboardService
{
    public function __construct(private readonly DashboardRepository $dashboard) {}

    /**
     * @return array{categories: int, articles: int, public_articles: int}
     */
    public function summary(): array
    {
        return [
            'categories' => $this->dashboard->categories(),
            ...$this->dashboard->articleCounts(),
        ];
    }
}
