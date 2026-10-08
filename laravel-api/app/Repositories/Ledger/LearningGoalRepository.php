<?php

declare(strict_types=1);

namespace App\Repositories\Ledger;

use App\Constants\LedgerConst;
use App\Enums\GoalStatus;
use App\Models\Ledger\LearningGoal;
use App\Repositories\CrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class LearningGoalRepository extends CrudRepository
{
    public function __construct(LearningGoal $model)
    {
        parent::__construct($model);
    }

    public function list(array $payload): LengthAwarePaginator
    {
        $query = $this->model->query()->with('skill:id,name,current_level');

        $this->applyFilters($query, $payload, ['id', 'skill_id', 'status']);
        $this->applySorting($query, $this->allowedSort($payload, ['id', 'target_level', 'target_date', 'status', 'updated_at']), 'target_date');

        return $query->paginate($payload['per_page'] ?? LedgerConst::PER_PAGE, ['*'], 'page', $payload['page'] ?? 1);
    }

    public function find(int $id): LearningGoal
    {
        /** @var LearningGoal */
        return $this->model->newQuery()->with('skill:id,current_level')->findOrFail($id);
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
