<?php

namespace App\Http\Requests\Management\EntryMgmt;

use App\Constants\CommonVal;
use App\Enums\IsDelete;
use App\Enums\IsDisplay;
use App\Enums\StatusEnum;
use App\Rules\LayoutStructureRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEntryMgmtRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'slug' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'status' => ['required', Rule::enum(StatusEnum::class)],
            'is_display' => ['required', Rule::enum(IsDisplay::class)],
            'rank_order' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
            'is_delete' => ['required', Rule::enum(IsDelete::class)],
            'layout_structure' => ['nullable', new LayoutStructureRule(100, 'entry_desc_id')],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_display' => $this->boolean('is_display') ? 1 : 0,
        ]);
    }

    public function attributes(): array
    {
        return [
            'parent_id' => __('messages.parent_id'),
            'name' => __('messages.name'),
            'slug' => __('messages.slug'),
            'status' => __('messages.status'),
            'is_display' => __('messages.is_display'),
            'rank_order' => __('messages.rank_order'),
            'is_delete' => __('messages.is_delete'),
        ];
    }
}
