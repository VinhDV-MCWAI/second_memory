<?php

namespace App\Http\Requests\History\Master\AdminMstHist;

use App\Constants\CommonVal;
use App\Enums\Gender;
use App\Enums\IsActive;
use App\Enums\StatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ListAdminMstHistRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'admin_mst_id' => ['nullable', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
            'user_name' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'first_name' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:20'],
            'last_name' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:20'],
            'address' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:100'],
            'phone_number' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:'.CommonVal::MAX_PHONE_NUMBER],
            'birth' => ['nullable', 'date_format:'.CommonVal::DATE_FORMAT, 'after_or_equal:'.CommonVal::MIN_DATE, 'before_or_equal:'.CommonVal::MAX_DATE],
            'gender' => ['nullable', new Enum(Gender::class)],
            'status' => ['nullable', new Enum(StatusEnum::class)],
            'is_active' => ['nullable', new Enum(IsActive::class)],
            'avatar' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:30'],
            'action' => ['nullable'],
            'author_id' => ['nullable', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
            'from_date' => [
                'nullable',
                'date_format:'.CommonVal::DATE_FORMAT,
                'after_or_equal:'.CommonVal::MIN_DATE,
                'before_or_equal:'.CommonVal::MAX_DATE,
            ],
            'to_date' => [
                'nullable',
                'date_format:'.CommonVal::DATE_FORMAT,
                'after_or_equal:'.CommonVal::MIN_DATE,
                'before_or_equal:'.CommonVal::MAX_DATE,
                'after:from_date',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'admin_mst_id' => __('messages.admin_mst_id'),
            'user_name' => __('messages.user_name'),
            'first_name' => __('messages.first_name'),
            'last_name' => __('messages.last_name'),
            'address' => __('messages.address'),
            'phone_number' => __('messages.phone_number'),
            'birth' => __('messages.birth'),
            'gender' => __('messages.gender'),
            'status' => __('messages.status'),
            'is_active' => __('messages.is_active'),
            'avatar' => __('messages.avatar'),
            'action' => __('messages.action'),
            'author_id' => __('messages.author_id'),
            'from_date' => __('messages.from_date'),
            'to_date' => __('messages.to_date'),
        ];
    }
}
