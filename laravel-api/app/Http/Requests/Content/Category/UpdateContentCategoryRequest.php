<?php

declare(strict_types=1);

namespace App\Http\Requests\Content\Category;

use App\Constants\ContentConst;
use App\Rules\UniqueIgnoringCase;
use Illuminate\Foundation\Http\FormRequest;

class UpdateContentCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:'.ContentConst::CATEGORY_NAME_MAX, new UniqueIgnoringCase('content_category', 'name', $this->route('id'))],
            'description' => ['nullable', 'string', 'max:'.ContentConst::CATEGORY_DESCRIPTION_MAX],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:'.ContentConst::SORT_ORDER_MAX],
            'is_public' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('messages.name'),
            'description' => __('messages.description'),
            'sort_order' => __('messages.sort_order'),
            'is_public' => __('messages.is_public'),
        ];
    }
}
