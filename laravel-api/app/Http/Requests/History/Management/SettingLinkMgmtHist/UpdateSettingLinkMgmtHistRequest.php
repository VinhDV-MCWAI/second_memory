<?php

namespace App\Http\Requests\History\Management\SettingLinkMgmtHist;

use App\Constants\CommonVal;
use App\Models\History\Management\SettingLinkMgmtHist;
use App\Models\Management\SettingLinkMgmt;
use App\Models\Master\AdminMst;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingLinkMgmtHistRequest extends FormRequest
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
            'id' => ['required', 'integer', 'min:1', Rule::exists(SettingLinkMgmtHist::class, 'id')],
            'setting_link_mgmt_id' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER, Rule::exists(SettingLinkMgmt::class, 'id')],
            'key' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:30'],
            'value' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:100'],
            'action' => ['required', 'integer', 'min:'.CommonVal::MIN_VARCHAR, 'max:'.CommonVal::MAX_PHONE_NUMBER],
            'author_id' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER, Rule::exists(AdminMst::class, 'id')],
        ];
    }

    public function attributes(): array
    {
        return [
            'setting_link_mgmt_id' => __('messages.setting_link_mgmt_id'),
            'key' => __('messages.key'),
            'value' => __('messages.value'),
            'action' => __('messages.action'),
            'author_id' => __('messages.author_id'),
        ];
    }
}
