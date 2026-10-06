<?php

namespace App\Http\Requests\Management\SettingLinkMgmt;

use App\Constants\CommonVal;
use App\Enums\IsDelete;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreSettingLinkMgmtRequest extends FormRequest
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
            'key' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:30'],
            'value' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:100'],
            'is_delete' => ['required', new Enum(IsDelete::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'key' => __('messages.key'),
            'value' => __('messages.value'),
            'is_delete' => __('messages.is_delete'),
        ];
    }
}
