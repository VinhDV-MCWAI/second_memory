<?php

declare(strict_types=1);

namespace App\Http\Requests\History\Master\DepartmentMstHist;

use App\Constants\CommonVal;
use App\Enums\StatusEnum;
use App\Http\Requests\ListRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class ListDepartmentMstHistRequest extends ListRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filters(): array
    {
        return [
            'department_mst_id' => ['nullable', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
            'code' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'name' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'status' => ['nullable', Rule::enum(StatusEnum::class)],
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
            'department_mst_id' => __('messages.department_mst_id'),
            'code' => __('messages.code'),
            'name' => __('messages.name'),
            'status' => __('messages.status'),
            'action' => __('messages.action'),
            'author_id' => __('messages.author_id'),
            'from_date' => __('messages.from_date'),
            'to_date' => __('messages.to_date'),
        ];
    }
}
