<?php

declare(strict_types=1);

namespace App\Http\Requests\Master\DepartmentMst;

use App\Constants\CommonVal;
use App\Enums\DepartmentStatus;
use App\Models\Master\DepartmentMst;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentMstRequest extends FormRequest
{
    // ...
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'min:1', Rule::exists(DepartmentMst::class, 'id')],
            'code' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'name' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'status' => ['required', Rule::enum(DepartmentStatus::class)],
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
