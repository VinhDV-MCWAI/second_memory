<?php

declare(strict_types=1);

namespace App\Http\Requests\Ledger\SkillLevel;

use App\Http\Requests\ListRequest;

class ListSkillLevelRequest extends ListRequest
{
    protected function filters(): array
    {
        return [
            'skill_id' => ['required', 'integer'],
        ];
    }
}
