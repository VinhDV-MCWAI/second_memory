<?php

declare(strict_types=1);

namespace App\Http\Requests\Ledger\Skill;

use App\Constants\LedgerConst;
use App\Enums\SkillLevel;
use App\Models\Ledger\Tag;
use App\Rules\UniqueIgnoringCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A skill is created together with its first level entry (RFC-002 §4.2).
 */
class StoreSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:'.LedgerConst::SKILL_NAME_MAX, new UniqueIgnoringCase('skill')],
            'category' => ['required', 'string', 'max:'.LedgerConst::SKILL_CATEGORY_MAX],
            'description' => ['nullable', 'string', 'max:'.LedgerConst::SKILL_DESCRIPTION_MAX],
            'is_public' => ['sometimes', 'boolean'],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => ['integer', 'distinct', Rule::exists(Tag::class, 'id')],
            'level' => ['required', Rule::enum(SkillLevel::class)],
            'changed_on' => ['sometimes', 'date_format:'.LedgerConst::DATE_FORMAT, 'before_or_equal:today'],
            'reason' => ['nullable', 'string', 'max:'.LedgerConst::LEVEL_REASON_MAX],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('messages.name'),
            'category' => __('messages.category'),
            'description' => __('messages.description'),
            'is_public' => __('messages.is_public'),
            'tag_ids' => __('messages.tag_ids'),
            'level' => __('messages.level'),
            'changed_on' => __('messages.changed_on'),
            'reason' => __('messages.reason'),
        ];
    }
}
