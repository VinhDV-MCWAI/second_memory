<?php

namespace App\Http\Requests\Master\PolicyDepartmentMst;

use App\Constants\CommonVal;
use App\Enums\IsDelete;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StorePolicyDepartmentMstRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'table_name' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:20'],
            'row_id' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
            'is_delete' => ['required', new Enum(IsDelete::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'table_name' => __('messages.table_name'),
            'row_id' => __('messages.row_id'),
            'is_delete' => __('messages.is_delete'),
        ];
    }
}
