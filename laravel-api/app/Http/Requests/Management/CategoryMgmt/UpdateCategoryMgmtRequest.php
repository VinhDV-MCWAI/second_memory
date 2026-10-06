<?php

namespace App\Http\Requests\Management\CategoryMgmt;

use App\Constants\CommonVal;
use App\Enums\IsActive;
use App\Enums\IsDelete;
use App\Enums\StatusEnum;
use App\Models\Management\CategoryMgmt;
use App\Rules\LayoutStructureRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryMgmtRequest extends FormRequest
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
            'id' => ['required', 'integer', 'min:1', Rule::exists(CategoryMgmt::class, 'id')],
            'name' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'slug' => ['required', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'description' => ['nullable', 'string', 'min:'.CommonVal::MIN_VARCHAR, 'max:150'],
            'status' => ['required', Rule::enum(StatusEnum::class)],
            'is_display' => ['required', Rule::enum(IsActive::class)],
            'rank_order' => ['required'],
            'is_delete' => ['required', Rule::enum(IsDelete::class)],
            'layout_structure' => ['nullable', new LayoutStructureRule(100, 'entry_mgmt_id')],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('messages.name'),
            'slug' => __('messages.slug'),
            'description' => __('messages.description'),
            'status' => __('messages.status'),
            'is_display' => __('messages.is_display'),
            'rank_order' => __('messages.rank_order'),
            'is_delete' => __('messages.is_delete'),
        ];
    }
}
