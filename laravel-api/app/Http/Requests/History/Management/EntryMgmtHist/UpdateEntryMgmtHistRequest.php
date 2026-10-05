<?php

namespace App\Http\Requests\History\Management\EntryMgmtHist;

use Illuminate\Foundation\Http\FormRequest;
use App\Constants\CommonVal;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use App\Models\History\Management\EntryMgmtHist;
use App\Enums\IsActive;
use App\Enums\StatusEnum;
use App\Models\Management\EntryMgmt;

class UpdateEntryMgmtHistRequest extends FormRequest
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
            'id' => ['required', 'integer', 'min:1', Rule::exists(EntryMgmtHist::class, 'id')],
            'entry_mgmt_id' => ['required', 'integer', 'min:' . CommonVal::MIN_INTEGER, 'max:' . CommonVal::MAX_INTEGER, Rule::exists(EntryMgmt::class, 'id'),],
            'parent_id' => ['integer', 'min:' . CommonVal::MIN_INTEGER, 'max:' . CommonVal::MAX_INTEGER,],
            'name' => ['string', 'min:' . CommonVal::MIN_VARCHAR, 'max:50',],
            'slug' => ['string', 'min:' . CommonVal::MIN_VARCHAR, 'max:50',],
            'status' => [new Enum(StatusEnum::class),],
            'is_display' => [new Enum(IsActive::class),],
            'rank_order' => [],
            'action' => ['required',],
            'author_id' => ['required', 'integer', 'min:' . CommonVal::MIN_INTEGER, 'max:' . CommonVal::MAX_INTEGER,],
        ];
    }

    public function attributes(): array
    {
        return [
            'entry_mgmt_id' => __('messages.entry_mgmt_id'),
            'parent_id' => __('messages.parent_id'),
            'name' => __('messages.name'),
            'slug' => __('messages.slug'),
            'status' => __('messages.status'),
            'is_display' => __('messages.is_display'),
            'rank_order' => __('messages.rank_order'),
            'action' => __('messages.action'),
            'author_id' => __('messages.author_id'),
        ];
    }
}
