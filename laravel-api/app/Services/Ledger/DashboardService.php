<?php

declare(strict_types=1);

namespace App\Services\Ledger;

use App\Enums\SkillLevel;
use App\Repositories\Ledger\DashboardRepository;

/**
 * Real numbers for the admin dashboard instead of the old sample values (REQ-002 US-7).
 */
final class DashboardService
{
    public function __construct(private readonly DashboardRepository $dashboard) {}

    /**
     * @return array{skills: int, public_skills: int, evidence: int, public_evidence: int, open_goals: int, levels: list<array{level: int, level_label: string, count: int}>}
     */
    public function summary(): array
    {
        $perLevel = $this->dashboard->skillsPerLevel();

        return [
            ...$this->dashboard->skillCounts(),
            ...$this->dashboard->evidenceCounts(),
            'open_goals' => $this->dashboard->openGoals(),
            // Every level is listed, also those without skills, so the chart keeps its scale
            'levels' => array_map(fn (SkillLevel $level): array => [
                'level' => $level->value,
                'level_label' => $level->label(),
                'count' => $perLevel[$level->value] ?? 0,
            ], SkillLevel::cases()),
        ];
    }
}
