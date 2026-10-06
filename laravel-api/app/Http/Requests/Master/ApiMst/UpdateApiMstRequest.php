<?php

namespace App\Http\Requests\Master\ApiMst;

use App\Constants\CommonVal;
use App\Enums\IsActive;
use App\Enums\IsDelete;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateApiMstRequest extends FormRequest
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
            'id' => ['required', 'integer', 'min:1', Rule::exists(ApiMst::class, 'id')],
            'type' => ['required'],
            'name' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'path' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:100'],
            'is_active' => ['required', new Enum(IsActive::class)],
            'feature_mst_id' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER, Rule::exists(FeatureMst::class, 'id')],
            'is_delete' => [new Enum(IsDelete::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => __('messages.type'),
            'name' => __('messages.name'),
            'path' => __('messages.path'),
            'is_active' => __('messages.is_active'),
            'feature_mst_id' => __('messages.feature_mst_id'),
            'is_delete' => __('messages.is_delete'),
        ];
    }
}
