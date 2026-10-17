<?php

declare(strict_types=1);

namespace App\Http\Requests\Master\EndUser;

use App\Constants\EndUserConst;
use App\Enums\IsActive;
use App\Http\Requests\ListRequest;
use Illuminate\Validation\Rule;

class ListEndUserRequest extends ListRequest
{
    protected function filters(): array
    {
        return [
            'email' => ['nullable', 'string', 'max:'.EndUserConst::EMAIL_MAX],
            'display_name' => ['nullable', 'string', 'max:'.EndUserConst::DISPLAY_NAME_MAX],
            'is_active' => ['nullable', Rule::enum(IsActive::class)],
        ];
    }
}
