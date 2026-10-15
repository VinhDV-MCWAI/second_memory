<?php

declare(strict_types=1);

namespace App\Http\Requests\Ledger\LearningGoal;

use App\Enums\GoalStatus;
use App\Http\Requests\ListRequest;
use Illuminate\Validation\Rule;

class ListLearningGoalRequest extends ListRequest
{
    protected function filters(): array
    {
        return [
            'skill_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(GoalStatus::class)],
        ];
    }
}
