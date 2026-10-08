<?php

declare(strict_types=1);

namespace App\Http\Requests\Ledger\Skill;

use App\Constants\LedgerConst;
use App\Models\Ledger\Tag;
use App\Rules\UniqueIgnoringCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * No level field: levels change only through skill-level/store, so the history stays complete.
 * The slug is not editable either; public URLs stay stable across renames.
 */
class UpdateSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:'.LedgerConst::SKILL_NAME_MAX, new UniqueIgnoringCase('skill', 'name', $this->route('id'))],
            'category' => ['sometimes', 'string', 'max:'.LedgerConst::SKILL_CATEGORY_MAX],
            'description' => ['nullable', 'string', 'max:'.LedgerConst::SKILL_DESCRIPTION_MAX],
            'is_public' => ['sometimes', 'boolean'],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => ['integer', 'distinct', Rule::exists(Tag::class, 'id')],
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
        ];
    }
}
