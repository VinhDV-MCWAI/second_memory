<?php

namespace App\Http\Requests\Master\DepartmentMst;

use App\Constants\CommonVal;
use App\Enums\DepartmentStatus;
use App\Enums\IsDelete;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentMstRequest extends FormRequest
{
    // ...
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50', Rule::unique('department_mst', 'code')->where(fn ($query) => $query->where('is_delete', IsDelete::FALSE))],
            'name' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'status' => ['required', Rule::enum(DepartmentStatus::class)],
            'is_delete' => ['required', Rule::enum(IsDelete::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => __('messages.code'),
            'name' => __('messages.name'),
            'status' => __('messages.status'),
            'is_delete' => __('messages.is_delete'),
        ];
    }
}
