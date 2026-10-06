<?php

declare(strict_types=1);

namespace App\Http\Requests\Master\ApiRoleMst;

use App\Constants\CommonVal;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateApiRoleMstRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'insert' => ['nullable', 'array'],
            'insert.*' => 'array',
            'insert.*.api_mst_id' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
            'insert.*.role_mst_id' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
            'delete' => ['nullable', 'array'],
            'delete.*' => 'array',
            'delete.*.api_mst_id' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
            'delete.*.role_mst_id' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
        ];
    }

    public function attributes(): array
    {
        return [
            'api_mst_id' => __('messages.api_mst_id'),
            'role_mst_id' => __('messages.role_mst_id'),
            'insert.*.api_mst_id' => __('messages.api_mst_id'),
            'delete.*.api_mst_id' => __('messages.api_mst_id'),
            'insert.*.role_mst_id' => __('messages.role_mst_id'),
            'delete.*.role_mst_id' => __('messages.role_mst_id'),
        ];
    }
}
