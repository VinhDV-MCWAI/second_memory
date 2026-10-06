<?php

namespace App\Http\Requests\Management\EntryDescriptionMgmt;

use App\Constants\CommonVal;
use App\Enums\IsActive;
use App\Enums\IsDelete;
use App\Enums\StatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ListEntryDescriptionMgmtRequest extends FormRequest
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
            'title' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:100'],
            'summary' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:255'],
            'article' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:'.CommonVal::MAX_VARCHAR],
            'status' => ['nullable', new Enum(StatusEnum::class)],
            'is_display' => ['nullable', new Enum(IsActive::class)],
            'rank_order' => ['nullable'],
            'is_delete' => ['nullable', new Enum(IsDelete::class)],
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
            'parent_id' => __('messages.parent_id'),
            'title' => __('messages.title'),
            'summary' => __('messages.summary'),
            'article' => __('messages.article'),
            'status' => __('messages.status'),
            'is_display' => __('messages.is_display'),
            'rank_order' => __('messages.rank_order'),
            'is_delete' => __('messages.is_delete'),
            'from_date' => __('messages.from_date'),
            'to_date' => __('messages.to_date'),
        ];
    }
}
