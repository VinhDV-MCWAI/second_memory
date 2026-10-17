<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use App\Models\Master\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMediaAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department_ids' => ['present', 'array'],
            'department_ids.*' => ['integer', 'distinct', Rule::exists(Department::class, 'id')],
        ];
    }

    public function attributes(): array
    {
        return ['department_ids' => __('messages.department'), 'department_ids.*' => __('messages.department')];
    }
}
