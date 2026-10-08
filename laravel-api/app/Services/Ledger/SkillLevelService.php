<?php

declare(strict_types=1);

namespace App\Services\Ledger;

use App\Constants\LedgerConst;
use App\Enums\AuditEvent;
use App\Enums\GoalStatus;
use App\Http\Resources\Ledger\SkillLevelResource;
use App\Repositories\Ledger\LearningGoalRepository;
use App\Repositories\Ledger\SkillLevelRepository;
use App\Repositories\Ledger\SkillRepository;
use App\Services\AuditLogger;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

/**
 * Level history (REQ-002 US-1, US-6): entries are appended, never changed. Each entry
 * refreshes skill.current_level and completes the open goals it reaches.
 */
final class SkillLevelService
{
    public function __construct(
        private readonly SkillLevelRepository $levels,
        private readonly SkillRepository $skills,
        private readonly LearningGoalRepository $goals,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function list(array $payload): AnonymousResourceCollection
    {
        return SkillLevelResource::collection($this->levels->list($payload));
    }

    /**
     * @param  array{skill_id: int|string, level: int|string, changed_on?: string, reason?: ?string}  $payload
     */
    public function record(array $payload): int
    {
        $skillId = (int) $payload['skill_id'];
        $changedOn = $payload['changed_on'] ?? now()->format(LedgerConst::DATE_FORMAT);

        $entry = $this->levels->append([
            'skill_id' => $skillId,
            'level' => (int) $payload['level'],
            'changed_on' => $changedOn,
            'reason' => $payload['reason'] ?? null,
            'admin_mst_id' => Auth::id(),
        ]);
        $this->auditLogger->record('skill_level', $entry->id, AuditEvent::CREATED, null, [
            'skill_id' => $skillId,
            'level' => $entry->level->value,
            'changed_on' => $changedOn,
            'reason' => $entry->reason,
        ]);

        $currentLevel = $this->levels->latestLevel($skillId);
        $this->skills->setCurrentLevel($skillId, $currentLevel);
        $this->achieveGoals($skillId, $currentLevel, $changedOn);

        return $entry->id;
    }

    private function achieveGoals(int $skillId, int $level, string $achievedOn): void
    {
        foreach ($this->goals->openGoalsReachedAt($skillId, $level) as $goal) {
            $this->goals->markAchieved($goal, $achievedOn);
            $this->auditLogger->record(
                'learning_goal',
                $goal->id,
                AuditEvent::UPDATED,
                ['status' => GoalStatus::OPEN->value, 'achieved_on' => null],
                ['status' => GoalStatus::ACHIEVED->value, 'achieved_on' => $achievedOn],
            );
        }
    }
}
