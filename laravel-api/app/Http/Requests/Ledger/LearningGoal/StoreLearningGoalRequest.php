<?php

declare(strict_types=1);

namespace App\Http\Requests\Ledger\LearningGoal;

use App\Constants\LedgerConst;
use App\Enums\SkillLevel;
use App\Models\Ledger\Skill;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A new goal is always open; the target must be above the skill's current level (LearningGoalService).
 */
class StoreLearningGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'skill_id' => ['required', 'integer', Rule::exists(Skill::class, 'id')],
            'target_level' => ['required', Rule::enum(SkillLevel::class)],
            'target_date' => ['nullable', 'date_format:'.LedgerConst::DATE_FORMAT],
            'note' => ['nullable', 'string', 'max:'.LedgerConst::GOAL_NOTE_MAX],
        ];
    }

    public function attributes(): array
    {
        return [
            'skill_id' => __('messages.skill_id'),
            'target_level' => __('messages.target_level'),
            'target_date' => __('messages.target_date'),
            'note' => __('messages.note'),
        ];
    }
}
