<?php

declare(strict_types=1);

namespace App\Http\Requests\Master\AdminMst;

use App\Constants\CommonVal;
use App\Enums\AdminRole;
use App\Enums\Gender;
use App\Enums\IsActive;
use App\Enums\IsDelete;
use App\Enums\StatusEnum;
use App\Http\Requests\ListRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class ListAdminMstRequest extends ListRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filters(): array
    {
        return [
            'email' => ['nullable', 'string', 'max:'.CommonVal::MAX_VARCHAR],
            'user_name' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'first_name' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:20'],
            'last_name' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:20'],
            'address' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:100'],
            'phone_number' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:'.CommonVal::MAX_PHONE_NUMBER],
            'birth' => ['nullable', 'date_format:'.CommonVal::DATE_FORMAT, 'after_or_equal:'.CommonVal::MIN_DATE, 'before_or_equal:'.CommonVal::MAX_DATE],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'status' => ['nullable', Rule::enum(StatusEnum::class)],
            'is_active' => ['nullable', Rule::enum(IsActive::class)],
            'role' => ['nullable', Rule::enum(AdminRole::class)],
            'avatar' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:30'],
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
            'user_name' => __('messages.user_name'),
            'first_name' => __('messages.first_name'),
            'last_name' => __('messages.last_name'),
            'address' => __('messages.address'),
            'phone_number' => __('messages.phone_number'),
            'birth' => __('messages.birth'),
            'gender' => __('messages.gender'),
            'status' => __('messages.status'),
            'is_active' => __('messages.is_active'),
            'role' => __('messages.role'),
            'avatar' => __('messages.avatar'),
            'is_delete' => __('messages.is_delete'),
            'from_date' => __('messages.from_date'),
            'to_date' => __('messages.to_date'),
        ];
    }
}
