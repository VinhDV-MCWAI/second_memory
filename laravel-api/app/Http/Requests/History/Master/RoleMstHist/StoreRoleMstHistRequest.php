<?php

namespace App\Http\Requests\History\Master\RoleMstHist;

use App\Constants\CommonVal;
use App\Enums\IsActive;
use App\Models\Master\RoleMst;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreRoleMstHistRequest extends FormRequest
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
            'role_mst_id' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER, Rule::exists(RoleMst::class, 'id')],
            'name' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:30'],
            'permission' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'is_active' => [new Enum(IsActive::class)],
            'action' => ['required'],
            'author_id' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
        ];
    }

    public function attributes(): array
    {
        return [
            'role_mst_id' => __('messages.role_mst_id'),
            'name' => __('messages.name'),
            'permission' => __('messages.permission'),
            'is_active' => __('messages.is_active'),
            'action' => __('messages.action'),
            'author_id' => __('messages.author_id'),
        ];
    }
}
