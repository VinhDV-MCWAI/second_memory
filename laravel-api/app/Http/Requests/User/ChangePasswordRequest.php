<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Constants\EndUserConst;
use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password:'.EndUserConst::GUARD],
            'password' => ['required', 'string', 'min:'.EndUserConst::PASSWORD_MIN, 'max:'.EndUserConst::PASSWORD_MAX, 'confirmed', 'different:current_password'],
        ];
    }

    public function attributes(): array
    {
        return ['current_password' => __('messages.password'), 'password' => __('messages.password')];
    }
}
