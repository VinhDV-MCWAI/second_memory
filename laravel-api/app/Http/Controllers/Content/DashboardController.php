<?php

declare(strict_types=1);

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Services\Content\DashboardService;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    /**
     * Category and article counts for the dashboard
     *
     * @return array{categories: int, articles: int, public_articles: int}
     */
    public function summary(): array
    {
        return $this->dashboard->summary();
    }
}
