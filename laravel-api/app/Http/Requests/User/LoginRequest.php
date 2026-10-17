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
            'email' => ['required', 'string', 'max:'.EndUserConst::EMAIL_MAX],
            'password' => ['required', 'string', 'max:'.EndUserConst::PASSWORD_MAX],
        ];
    }

    public function attributes(): array
    {
        return ['email' => __('messages.email'), 'password' => __('messages.password')];
    }
}
