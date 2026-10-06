<?php

namespace App\Http\Requests\History\Master\PolicyDepartmentMstHist;

use App\Constants\CommonVal;
use App\Models\Master\PolicyDepartmentMst;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePolicyDepartmentMstHistRequest extends FormRequest
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
            'policy_department_mst_id' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER, Rule::exists(PolicyDepartmentMst::class, 'id')],
            'table_name' => ['integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
            'row_id' => ['integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
            'action' => ['required'],
            'author_id' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
        ];
    }

    public function attributes(): array
    {
        return [
            'policy_department_mst_id' => __('messages.policy_department_mst_id'),
            'table_name' => __('messages.table_name'),
            'row_id' => __('messages.row_id'),
            'action' => __('messages.action'),
            'author_id' => __('messages.author_id'),
        ];
    }
}
