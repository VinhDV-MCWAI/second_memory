<?php

declare(strict_types=1);

namespace App\Http\Requests\Management\SettingLinkMgmt;

use App\Constants\CommonVal;
use App\Enums\IsDelete;
use App\Models\Management\SettingLinkMgmt;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingLinkMgmtRequest extends FormRequest
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
            'id' => ['required', 'integer', 'min:1', Rule::exists(SettingLinkMgmt::class, 'id')],
            'key' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:30'],
            'value' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:100'],
            'is_delete' => ['required', Rule::enum(IsDelete::class)],
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
