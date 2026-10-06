<?php

declare(strict_types=1);

namespace App\Http\Requests\Master\AdminRoleMst;

use App\Constants\CommonVal;
use App\Http\Requests\ListRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class ListAdminRoleMstRequest extends ListRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filters(): array
    {
        return [
            'admin_mst_id' => ['nullable', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
            'role_mst_id' => ['nullable', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
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
            'role_mst_id' => __('messages.role_mst_id'),
            'from_date' => __('messages.from_date'),
            'to_date' => __('messages.to_date'),
        ];
    }
}
