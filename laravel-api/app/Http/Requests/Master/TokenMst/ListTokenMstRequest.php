<?php

declare(strict_types=1);

namespace App\Http\Requests\Master\TokenMst;

use App\Constants\CommonVal;
use App\Http\Requests\ListRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class ListTokenMstRequest extends ListRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filters(): array
    {
        return [
            'token_hash' => ['nullable', 'string', 'max:'.CommonVal::MAX_VARCHAR],
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
