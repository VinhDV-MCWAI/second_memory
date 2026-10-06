<?php

namespace App\Http\Requests\Master\RoleMst;

use App\Constants\CommonVal;
use App\Enums\IsActive;
use App\Enums\IsDelete;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ListRoleMstRequest extends FormRequest
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
            'name' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:30'],
            'permission' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'is_active' => ['nullable', new Enum(IsActive::class)],
            'is_delete' => ['nullable', new Enum(IsDelete::class)],
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
            'name' => __('messages.name'),
            'permission' => __('messages.permission'),
            'is_active' => __('messages.is_active'),
            'is_delete' => __('messages.is_delete'),
            'from_date' => __('messages.from_date'),
            'to_date' => __('messages.to_date'),
        ];
    }
}
