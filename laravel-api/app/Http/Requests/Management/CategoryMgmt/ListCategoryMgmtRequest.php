<?php

declare(strict_types=1);

namespace App\Http\Requests\Management\CategoryMgmt;

use App\Constants\CommonVal;
use App\Enums\IsActive;
use App\Enums\IsDelete;
use App\Enums\StatusEnum;
use App\Http\Requests\ListRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class ListCategoryMgmtRequest extends ListRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filters(): array
    {
        return [
            'name' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'slug' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'description' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:150'],
            'status' => ['nullable', Rule::enum(StatusEnum::class)],
            'is_display' => ['nullable', Rule::enum(IsActive::class)],
            'rank_order' => ['nullable'],
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
            'name' => __('messages.name'),
            'slug' => __('messages.slug'),
            'description' => __('messages.description'),
            'status' => __('messages.status'),
            'is_display' => __('messages.is_display'),
            'rank_order' => __('messages.rank_order'),
            'is_delete' => __('messages.is_delete'),
            'from_date' => __('messages.from_date'),
            'to_date' => __('messages.to_date'),
        ];
    }
}
