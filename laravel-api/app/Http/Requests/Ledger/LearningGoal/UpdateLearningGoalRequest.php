<?php

declare(strict_types=1);

namespace App\Http\Requests\Ledger\LearningGoal;

use App\Constants\LedgerConst;
use App\Enums\GoalStatus;
use App\Enums\SkillLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The skill of a goal is fixed. `achieved` is set only by a level change (REQ-002 US-6),
 * so the status can be switched between open and dropped only.
 */
class UpdateLearningGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_level' => ['sometimes', Rule::enum(SkillLevel::class)],
            'target_date' => ['nullable', 'date_format:'.LedgerConst::DATE_FORMAT],
            'status' => ['sometimes', Rule::enum(GoalStatus::class)->only([GoalStatus::OPEN, GoalStatus::DROPPED])],
            'note' => ['nullable', 'string', 'max:'.LedgerConst::GOAL_NOTE_MAX],
        ];
    }

    public function attributes(): array
    {
        return [
            'target_level' => __('messages.target_level'),
            'target_date' => __('messages.target_date'),
            'status' => __('messages.status'),
            'note' => __('messages.note'),
        ];
    }
}
