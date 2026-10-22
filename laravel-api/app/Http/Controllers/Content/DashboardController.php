<?php

declare(strict_types=1);

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Services\Content\DashboardService;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    /**
     * Category and article counts, total views, and the articles read and saved most (private ones too) for the dashboard
     *
     * @return array{categories: int, articles: int, public_articles: int, views: int, most_read: list<array{id: int, title: string, slug: string, is_public: bool, count: int}>, most_saved: list<array{id: int, title: string, slug: string, is_public: bool, count: int}>}
     */
    public function summary(): array
    {
        return $this->dashboard->summary();
    }
}
