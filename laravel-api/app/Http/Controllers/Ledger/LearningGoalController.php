<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ledger;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ledger\LearningGoal\DeleteLearningGoalRequest;
use App\Http\Requests\Ledger\LearningGoal\ListLearningGoalRequest;
use App\Http\Requests\Ledger\LearningGoal\StoreLearningGoalRequest;
use App\Http\Requests\Ledger\LearningGoal\UpdateLearningGoalRequest;
use App\Http\Resources\Ledger\LearningGoalResource;
use App\Services\Ledger\LearningGoalService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LearningGoalController extends Controller
{
    public function __construct(private readonly LearningGoalService $goals) {}

    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, LearningGoalResource>>
     */
    public function list(ListLearningGoalRequest $request): AnonymousResourceCollection
    {
        return $this->goals->list($request->validated());
    }

    /**
     * Create an open goal; the target must be above the current level
     */
    public function store(StoreLearningGoalRequest $request): int
    {
        return $this->goals->store($request->validated());
    }

    /**
     * Update a goal (status open or dropped; achieved goals: date and note only)
     */
    public function update(UpdateLearningGoalRequest $request, string $id): int
    {
        return $this->goals->update([...$request->validated(), 'id' => $id]);
    }

    public function delete(DeleteLearningGoalRequest $request): void
    {
        $this->goals->delete($request->validated());
    }
}
