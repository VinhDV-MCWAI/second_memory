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
            'email' => ['required', 'string', 'email', 'max:'.EndUserConst::EMAIL_MAX, new UniqueIgnoringCase('end_user', 'email')],
            'display_name' => ['required', 'string', 'max:'.EndUserConst::DISPLAY_NAME_MAX],
            'password' => ['required', 'string', 'min:'.EndUserConst::PASSWORD_MIN, 'max:'.EndUserConst::PASSWORD_MAX, 'confirmed'],
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => __('messages.email'),
            'display_name' => __('messages.name'),
            'password' => __('messages.password'),
        ];
    }
}
