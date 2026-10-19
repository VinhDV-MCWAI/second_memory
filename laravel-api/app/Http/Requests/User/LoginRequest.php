<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Constants\EndUserConst;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:'.EndUserConst::USERNAME_MAX],
            'password' => ['required', 'string', 'max:'.EndUserConst::PASSWORD_MAX],
        ];
    }

    public function attributes(): array
    {
        return ['username' => __('messages.user_name'), 'password' => __('messages.password')];
    }
}
