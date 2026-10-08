<?php

declare(strict_types=1);

namespace App\Repositories\Ledger;

use App\Enums\GoalStatus;
use App\Models\Ledger\LearningGoal;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;

class LearningGoalRepository extends BaseRepository
{
    public function __construct(LearningGoal $model)
    {
        parent::__construct($model);
    }

    /**
     * Open goals of the skill whose target is reached at the given level.
     *
     * @return Collection<int, LearningGoal>
     */
    public function openGoalsReachedAt(int $skillId, int $level): Collection
    {
        /** @var Collection<int, LearningGoal> */
        return $this->model->newQuery()
            ->where('skill_id', $skillId)
            ->where('status', GoalStatus::OPEN)
            ->where('target_level', '<=', $level)
            ->get();
    }

    public function markAchieved(LearningGoal $goal, string $achievedOn): void
    {
        $goal->update(['status' => GoalStatus::ACHIEVED, 'achieved_on' => $achievedOn]);
    }
}
