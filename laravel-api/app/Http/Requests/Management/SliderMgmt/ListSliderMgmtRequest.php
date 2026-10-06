<?php

declare(strict_types=1);

namespace App\Http\Requests\Management\SliderMgmt;

use App\Constants\CommonVal;
use App\Enums\IsDelete;
use App\Enums\StatusEnum;
use App\Http\Requests\ListRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class ListSliderMgmtRequest extends ListRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filters(): array
    {
        return [
            'title' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'slug' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'link' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:100'],
            'image' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:100'],
            'status' => ['nullable', Rule::enum(StatusEnum::class)],
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
            'title' => __('messages.title'),
            'slug' => __('messages.slug'),
            'link' => __('messages.link'),
            'image' => __('messages.image'),
            'status' => __('messages.status'),
            'is_delete' => __('messages.is_delete'),
            'from_date' => __('messages.from_date'),
            'to_date' => __('messages.to_date'),
        ];
    }
}
