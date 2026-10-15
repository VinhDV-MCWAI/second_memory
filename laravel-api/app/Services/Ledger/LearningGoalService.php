<?php

declare(strict_types=1);

namespace App\Services\Ledger;

use App\Constants\Messages;
use App\Enums\GoalStatus;
use App\Http\Resources\Ledger\LearningGoalResource;
use App\Repositories\Ledger\LearningGoalRepository;
use App\Repositories\Ledger\SkillRepository;
use App\Services\AuditedCrudService;
use App\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

/**
 * Learning goals (REQ-002 US-6). Goals become `achieved` only through SkillLevelService;
 * here they are created open and moved between open and dropped.
 */
class LearningGoalService extends AuditedCrudService
{
    protected string $resource = LearningGoalResource::class;

    protected string $auditableType = 'learning_goal';

    public function __construct(
        private readonly LearningGoalRepository $goals,
        private readonly SkillRepository $skills,
        AuditLogger $auditLogger,
    ) {
        parent::__construct($goals, $auditLogger);
    }

    /**
     * @throws ValidationException when the skill is already at or above the target
     */
    public function store(array $payload): int
    {
        $this->ensureAboveCurrentLevel((int) $payload['target_level'], $this->skills->currentLevel((int) $payload['skill_id']));

        return parent::store([...$payload, 'status' => GoalStatus::OPEN->value]);
    }

    /**
     * @throws ValidationException when an achieved goal would change its target or status,
     *                             or an open goal would already be reached
     */
    public function update(array $payload): int
    {
        $goal = $this->goals->find((int) $payload['id']);
        $targetLevel = (int) ($payload['target_level'] ?? $goal->target_level->value);
        $status = $payload['status'] ?? $goal->status->value;

        if ($goal->status === GoalStatus::ACHIEVED) {
            $changed = array_filter([
                'target_level' => $targetLevel !== $goal->target_level->value,
                'status' => $status !== $goal->status->value,
            ]);
            if ($changed !== []) {
                throw ValidationException::withMessages(array_map(fn () => Messages::E0023, $changed));
            }
        } elseif ($status === GoalStatus::OPEN->value) {
            $this->ensureAboveCurrentLevel($targetLevel, $goal->skill->current_level->value);
        }

        return parent::update($payload);
    }

    /**
     * A goal the skill already reached would never be auto-achieved, so it is refused.
     *
     * @throws ValidationException
     */
    private function ensureAboveCurrentLevel(int $targetLevel, int $currentLevel): void
    {
        if ($targetLevel <= $currentLevel) {
            throw ValidationException::withMessages(['target_level' => Messages::E0022]);
        }
    }
}
