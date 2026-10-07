<?php

declare(strict_types=1);

namespace App\Http\Requests\Master\ApiMst;

use App\Constants\CommonVal;
use App\Enums\IsActive;
use App\Enums\IsDelete;
use App\Http\Requests\ListRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class ListApiMstRequest extends ListRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filters(): array
    {
        return [
            'type' => ['nullable'],
            'name' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'path' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:100'],
            'is_active' => ['nullable', Rule::enum(IsActive::class)],
            'feature_mst_id' => ['nullable', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
            'is_delete' => ['nullable', Rule::enum(IsDelete::class)],
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
            'type' => __('messages.type'),
            'name' => __('messages.name'),
            'path' => __('messages.path'),
            'is_active' => __('messages.is_active'),
            'feature_mst_id' => __('messages.feature_mst_id'),
            'is_delete' => __('messages.is_delete'),
            'from_date' => __('messages.from_date'),
            'to_date' => __('messages.to_date'),
        ];
    }
}
