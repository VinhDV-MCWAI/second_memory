<?php

declare(strict_types=1);

namespace App\Http\Requests\Custom\Credential;

use App\Constants\CommonVal;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'user_name' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'password' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'user_name' => __('messages.user_name'),
            'password' => __('messages.password'),
        ];
    }
}
