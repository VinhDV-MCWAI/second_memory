<?php

declare(strict_types=1);

namespace App\Http\Requests\Ledger\Skill;

use App\Constants\LedgerConst;
use App\Enums\SkillLevel;
use App\Http\Requests\ListRequest;
use Illuminate\Validation\Rule;

class ListSkillRequest extends ListRequest
{
    protected function filters(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:'.LedgerConst::SKILL_NAME_MAX],
            'category' => ['nullable', 'string', 'max:'.LedgerConst::SKILL_CATEGORY_MAX],
            'is_public' => ['nullable', 'boolean'],
            'current_level' => ['nullable', Rule::enum(SkillLevel::class)],
            'tag_id' => ['nullable', 'integer'],
        ];
    }
}
