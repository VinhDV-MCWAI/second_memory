<?php

declare(strict_types=1);

namespace App\Http\Requests\History\Master\PolicyDepartmentMstHist;

use App\Constants\CommonVal;
use App\Models\History\Master\PolicyDepartmentMstHist;
use App\Models\Master\PolicyDepartmentMst;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePolicyDepartmentMstHistRequest extends FormRequest
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
            'id' => ['required', 'integer', 'min:1', Rule::exists(PolicyDepartmentMstHist::class, 'id')],
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
