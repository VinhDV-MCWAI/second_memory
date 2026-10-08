<?php

declare(strict_types=1);

namespace App\Http\Requests\Ledger\SkillLevel;

use App\Constants\LedgerConst;
use App\Enums\SkillLevel;
use App\Models\Ledger\Skill;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSkillLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'skill_id' => ['required', 'integer', Rule::exists(Skill::class, 'id')],
            'level' => ['required', Rule::enum(SkillLevel::class)],
            'changed_on' => ['sometimes', 'date_format:'.LedgerConst::DATE_FORMAT, 'before_or_equal:today'],
            'reason' => ['nullable', 'string', 'max:'.LedgerConst::LEVEL_REASON_MAX],
        ];
    }

    public function attributes(): array
    {
        return [
            'skill_id' => __('messages.skill_id'),
            'level' => __('messages.level'),
            'changed_on' => __('messages.changed_on'),
            'reason' => __('messages.reason'),
        ];
    }
}
