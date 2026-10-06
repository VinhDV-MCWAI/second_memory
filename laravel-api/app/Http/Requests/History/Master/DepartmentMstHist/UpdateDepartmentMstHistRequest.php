<?php

namespace App\Http\Requests\History\Master\DepartmentMstHist;

use App\Constants\CommonVal;
use App\Enums\StatusEnum;
use App\Models\History\Master\DepartmentMstHist;
use App\Models\Master\DepartmentMst;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateDepartmentMstHistRequest extends FormRequest
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
            'id' => ['required', 'integer', 'min:1', Rule::exists(DepartmentMstHist::class, 'id')],
            'department_mst_id' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER, Rule::exists(DepartmentMst::class, 'id')],
            'code' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'name' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'status' => [new Enum(StatusEnum::class)],
            'action' => ['required'],
            'author_id' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
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
        ];
    }
}
