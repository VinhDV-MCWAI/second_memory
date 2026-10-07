<?php

declare(strict_types=1);

namespace App\Http\Requests\Management\UserMgmt;

use App\Constants\CommonVal;
use App\Enums\Gender;
use App\Enums\IsActive;
use App\Enums\IsDelete;
use App\Enums\UserStatus;
use App\Models\Management\UserMgmt;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserMgmtRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc,dns', 'min:'.CommonVal::MIN_VARCHAR, 'max:30', Rule::unique(UserMgmt::class, 'email')],
            'user_name' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50', Rule::unique(UserMgmt::class, 'user_name')],
            'password' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:100'],
            'first_name' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:20'],
            'last_name' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:20'],
            'address' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:100'],
            'phone_number' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:'.CommonVal::MAX_PHONE_NUMBER],
            'birth' => ['nullable', 'date_format:'.CommonVal::DATE_FORMAT, 'after_or_equal:'.CommonVal::MIN_DATE, 'before_or_equal:'.CommonVal::MAX_DATE],
            'gender' => ['required', Rule::enum(Gender::class)],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'is_active' => ['required', Rule::enum(IsActive::class)],
            'avatar' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:30'],
            'is_delete' => ['required', Rule::enum(IsDelete::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => __('messages.email'),
            'user_name' => __('messages.user_name'),
            'password' => __('messages.password'),
            'first_name' => __('messages.first_name'),
            'last_name' => __('messages.last_name'),
            'address' => __('messages.address'),
            'phone_number' => __('messages.phone_number'),
            'birth' => __('messages.birth'),
            'gender' => __('messages.gender'),
            'status' => __('messages.status'),
            'is_active' => __('messages.is_active'),
            'avatar' => __('messages.avatar'),
            'is_delete' => __('messages.is_delete'),
        ];
    }
}
