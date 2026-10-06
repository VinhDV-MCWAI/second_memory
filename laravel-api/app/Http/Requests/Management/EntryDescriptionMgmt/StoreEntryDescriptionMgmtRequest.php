<?php

namespace App\Http\Requests\Management\EntryDescriptionMgmt;

use App\Constants\CommonVal;
use App\Enums\IsDelete;
use App\Enums\StatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreEntryDescriptionMgmtRequest extends FormRequest
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
            'title' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:100'],
            'summary' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:255'],
            'article' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:'.CommonVal::MAX_TEXT],
            'status' => ['required', new Enum(StatusEnum::class)],
            'is_display' => ['required', 'boolean'],
            'rank_order' => ['required', 'integer'],
            'is_delete' => ['required', new Enum(IsDelete::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => __('messages.title'),
            'summary' => __('messages.summary'),
            'article' => __('messages.article'),
            'status' => __('messages.status'),
            'is_display' => __('messages.is_display'),
            'rank_order' => __('messages.rank_order'),
            'is_delete' => __('messages.is_delete'),
        ];
    }
}
