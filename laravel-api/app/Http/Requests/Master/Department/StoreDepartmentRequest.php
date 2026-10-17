<?php

declare(strict_types=1);

namespace App\Http\Requests\Master\Department;

use App\Constants\DepartmentConst;
use App\Enums\IsActive;
use App\Rules\UniqueIgnoringCase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:'.DepartmentConst::CODE_MAX, new UniqueIgnoringCase('department', 'code', $this->route('id'))],
            'name' => ['required', 'string', 'max:'.DepartmentConst::NAME_MAX],
            'description' => ['nullable', 'string', 'max:'.DepartmentConst::DESCRIPTION_MAX],
            'is_active' => ['required', Rule::enum(IsActive::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => __('messages.code'),
            'name' => __('messages.name'),
            'description' => __('messages.description'),
            'is_active' => __('messages.is_active'),
        ];
    }
}
