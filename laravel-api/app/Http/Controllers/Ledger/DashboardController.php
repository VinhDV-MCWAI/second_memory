<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ledger;

use App\Http\Controllers\Controller;
use App\Services\Ledger\DashboardService;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

    /**
     * Skill Ledger counts for the dashboard
     *
     * @return array{skills: int, public_skills: int, evidence: int, public_evidence: int, open_goals: int, levels: list<array{level: int, level_label: string, count: int}>}
     */
    public function summary(): array
    {
        return $this->dashboard->summary();
    }
}
