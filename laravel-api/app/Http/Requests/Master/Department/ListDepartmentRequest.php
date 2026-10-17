<?php

declare(strict_types=1);

namespace App\Http\Requests\Master\Department;

use App\Constants\DepartmentConst;
use App\Enums\IsActive;
use App\Http\Requests\ListRequest;
use Illuminate\Validation\Rule;

class ListDepartmentRequest extends ListRequest
{
    protected function filters(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:'.DepartmentConst::CODE_MAX],
            'name' => ['nullable', 'string', 'max:'.DepartmentConst::NAME_MAX],
            'is_active' => ['nullable', Rule::enum(IsActive::class)],
        ];
    }
}
