<?php

declare(strict_types=1);

namespace App\Repositories\Ledger;

use App\Enums\GoalStatus;
use Illuminate\Support\Facades\DB;

/**
 * Counts for the admin dashboard (REQ-002 US-7): one query per table.
 */
final class DashboardRepository
{
    /**
     * @return array{skills: int, public_skills: int}
     */
    public function skillCounts(): array
    {
        $row = DB::table('skill')->selectRaw('count(*) AS skills, count(*) FILTER (WHERE is_public) AS public_skills')->first();

        return ['skills' => (int) $row?->skills, 'public_skills' => (int) $row?->public_skills];
    }

    /**
     * "Public" as the public page sees it: visible and not hidden by the importer.
     *
     * @return array{evidence: int, public_evidence: int}
     */
    public function evidenceCounts(): array
    {
        $row = DB::table('evidence')
            ->selectRaw('count(*) AS evidence, count(*) FILTER (WHERE is_public AND unpublished_at IS NULL) AS public_evidence')
            ->first();

        return ['evidence' => (int) $row?->evidence, 'public_evidence' => (int) $row?->public_evidence];
    }

    public function openGoals(): int
    {
        return DB::table('learning_goal')->where('status', GoalStatus::OPEN->value)->count();
    }

    /**
     * @return array<int, int> current level => number of skills
     */
    public function skillsPerLevel(): array
    {
        return DB::table('skill')
            ->select('current_level')
            ->selectRaw('count(*) AS total')
            ->groupBy('current_level')
            ->pluck('total', 'current_level')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }
}
