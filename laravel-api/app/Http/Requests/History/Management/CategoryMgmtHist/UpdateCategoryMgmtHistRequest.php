<?php

namespace App\Http\Requests\History\Management\CategoryMgmtHist;

use App\Constants\CommonVal;
use App\Enums\IsActive;
use App\Enums\StatusEnum;
use App\Models\History\Management\CategoryMgmtHist;
use App\Models\Management\CategoryMgmt;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateCategoryMgmtHistRequest extends FormRequest
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
            'id' => ['required', 'integer', 'min:1', Rule::exists(CategoryMgmtHist::class, 'id')],
            'category_mgmt_id' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER, Rule::exists(CategoryMgmt::class, 'id')],
            'parent_id' => ['integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
            'name' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'slug' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:50'],
            'description' => ['string', 'min:'.CommonVal::MIN_VARCHAR, 'max:150'],
            'status' => [new Enum(StatusEnum::class)],
            'is_display' => [new Enum(IsActive::class)],
            'rank_order' => [],
            'action' => ['required'],
            'author_id' => ['required', 'integer', 'min:'.CommonVal::MIN_INTEGER, 'max:'.CommonVal::MAX_INTEGER],
        ];
    }

    public function attributes(): array
    {
        return [
            'category_mgmt_id' => __('messages.category_mgmt_id'),
            'parent_id' => __('messages.parent_id'),
            'name' => __('messages.name'),
            'slug' => __('messages.slug'),
            'description' => __('messages.description'),
            'status' => __('messages.status'),
            'is_display' => __('messages.is_display'),
            'rank_order' => __('messages.rank_order'),
            'action' => __('messages.action'),
            'author_id' => __('messages.author_id'),
        ];
    }
}
