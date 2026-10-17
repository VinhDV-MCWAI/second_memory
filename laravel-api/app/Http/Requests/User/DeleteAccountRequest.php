<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Constants\EndUserConst;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Deleting the account asks for the password again (ADR-0020).
 */
class DeleteAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['password' => ['required', 'string', 'current_password:'.EndUserConst::GUARD]];
    }

    public function attributes(): array
    {
        return ['password' => __('messages.password')];
    }
}
