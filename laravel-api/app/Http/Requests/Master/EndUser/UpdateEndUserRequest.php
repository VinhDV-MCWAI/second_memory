<?php

declare(strict_types=1);

namespace App\Http\Requests\Master\EndUser;

use App\Constants\EndUserConst;
use App\Enums\IsActive;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Lock / unlock an end user, and optionally set a new password (there is no "forgot password" e-mail).
 */
class UpdateEndUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_active' => ['required', Rule::enum(IsActive::class)],
            'password' => ['nullable', 'string', 'min:'.EndUserConst::PASSWORD_MIN, 'max:'.EndUserConst::PASSWORD_MAX],
        ];
    }

    public function attributes(): array
    {
        return ['is_active' => __('messages.is_active'), 'password' => __('messages.password')];
    }
}
