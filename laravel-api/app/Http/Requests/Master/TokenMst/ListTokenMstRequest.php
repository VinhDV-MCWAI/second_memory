<?php

namespace App\Http\Requests\Master\TokenMst;

use App\Constants\CommonVal;
use Illuminate\Foundation\Http\FormRequest;

class ListTokenMstRequest extends FormRequest
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
            'account_id' => ['nullable', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
            'device_name' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:255'],
            'ip_address' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:255'],
            'expired_at' => ['nullable', 'date_format:'.CommonVal::DATE_FORMAT, 'after_or_equal:'.CommonVal::MIN_DATE, 'before_or_equal:'.CommonVal::MAX_DATE],
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
            'account_id' => __('messages.account_id'),
            'device_name' => __('messages.device_name'),
            'ip_address' => __('messages.ip_address'),
            'expired_at' => __('messages.expired_at'),
            'from_date' => __('messages.from_date'),
            'to_date' => __('messages.to_date'),
        ];
    }
}
