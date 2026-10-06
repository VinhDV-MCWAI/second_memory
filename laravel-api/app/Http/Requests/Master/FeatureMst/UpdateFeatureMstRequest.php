<?php

namespace App\Http\Requests\Master\FeatureMst;

use App\Constants\CommonVal;
use App\Enums\StatusEnum;
use App\Models\Master\FeatureMst;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateFeatureMstRequest extends FormRequest
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
            'id' => ['required', 'integer', 'min:1', Rule::exists(FeatureMst::class, 'id')],
            'name' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'group_name' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'status' => ['required', new Enum(StatusEnum::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('messages.name'),
            'group_name' => __('messages.group_name'),
            'status' => __('messages.status'),
            'is_delete' => __('messages.is_delete'),
        ];
    }
}
