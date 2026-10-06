<?php

namespace App\Http\Requests\History\Management\SettingLinkMgmtHist;

use App\Constants\CommonVal;
use App\Http\Requests\ListRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class ListSettingLinkMgmtHistRequest extends ListRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filters(): array
    {
        return [
            'setting_link_mgmt_id' => ['nullable', 'integer'],
            'setting_link_id' => ['nullable', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
            'key' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:30'],
            'value' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:100'],
            'action' => ['nullable'],
            'author_id' => ['nullable', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
            'from_date' => [
                'nullable',
                'date_format:'.CommonVal::DATE_FORMAT,
                'after_or_equal:'.CommonVal::MIN_DATE,
                'before_or_equal:'.CommonVal::MAX_DATE,
            ],
            'to_date' => [
                'nullable',
                'date_format:'.CommonVal::DATE_FORMAT,
                'after_or_equal:'.CommonVal::MIN_DATE,
                'before_or_equal:'.CommonVal::MAX_DATE,
                'after:from_date',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'setting_link_id' => __('messages.setting_link_id'),
            'key' => __('messages.key'),
            'value' => __('messages.value'),
            'action' => __('messages.action'),
            'author_id' => __('messages.author_id'),
            'from_date' => __('messages.from_date'),
            'to_date' => __('messages.to_date'),
        ];
    }
}
