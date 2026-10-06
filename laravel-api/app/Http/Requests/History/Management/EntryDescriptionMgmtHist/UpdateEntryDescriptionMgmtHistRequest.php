<?php

namespace App\Http\Requests\History\Management\EntryDescriptionMgmtHist;

use App\Constants\CommonVal;
use App\Enums\IsActive;
use App\Enums\StatusEnum;
use App\Models\History\Management\EntryDescriptionMgmtHist;
use App\Models\Management\EntryDescriptionMgmt;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEntryDescriptionMgmtHistRequest extends FormRequest
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
            'id' => ['required', 'integer', 'min:1', Rule::exists(EntryDescriptionMgmtHist::class, 'id')],
            'entry_description_mgmt_id' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER, Rule::exists(EntryDescriptionMgmt::class, 'id')],
            'parent_id' => ['integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
            'title' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:100'],
            'summary' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:255'],
            'article' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:'.CommonVal::MAX_VARCHAR],
            'status' => [Rule::enum(StatusEnum::class)],
            'is_display' => [Rule::enum(IsActive::class)],
            'rank_order' => [],
            'entry_id' => ['integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
            'action' => ['required'],
            'author_id' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
        ];
    }

    public function attributes(): array
    {
        return [
            'entry_description_mgmt_id' => __('messages.entry_description_mgmt_id'),
            'parent_id' => __('messages.parent_id'),
            'title' => __('messages.title'),
            'summary' => __('messages.summary'),
            'article' => __('messages.article'),
            'status' => __('messages.status'),
            'is_display' => __('messages.is_display'),
            'rank_order' => __('messages.rank_order'),
            'entry_id' => __('messages.entry_id'),
            'action' => __('messages.action'),
            'author_id' => __('messages.author_id'),
        ];
    }
}
