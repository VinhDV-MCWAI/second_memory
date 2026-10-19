<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Constants\EndUserConst;
use App\Rules\UniqueIgnoringCase;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => [
                'required', 'string', 'min:'.EndUserConst::USERNAME_MIN, 'max:'.EndUserConst::USERNAME_MAX,
                'regex:'.EndUserConst::USERNAME_PATTERN, new UniqueIgnoringCase('end_user', 'username'),
            ],
            'display_name' => ['required', 'string', 'max:'.EndUserConst::DISPLAY_NAME_MAX],
            'password' => ['required', 'string', 'min:'.EndUserConst::PASSWORD_MIN, 'max:'.EndUserConst::PASSWORD_MAX, 'confirmed'],
        ];
    }

    public function attributes(): array
    {
        return [
            'username' => __('messages.user_name'),
            'display_name' => __('messages.name'),
            'password' => __('messages.password'),
        ];
    }
}
