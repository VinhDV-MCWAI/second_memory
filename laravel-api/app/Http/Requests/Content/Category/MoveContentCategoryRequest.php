<?php

declare(strict_types=1);

namespace App\Http\Requests\Content\Category;

use App\Enums\MoveDirection;
use App\Models\Content\ContentCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A step up or down among the siblings (the ↑ ↓ buttons), or a drop in the tree (ADR-0023):
 * `parent_id` (null for the top) and `position` among its new siblings, 0 first.
 */
class MoveContentCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'direction' => ['required_without:position', Rule::enum(MoveDirection::class)],
            'position' => ['required_without:direction', 'integer', 'min:0'],
            'parent_id' => ['nullable', 'integer', Rule::exists(ContentCategory::class, 'id')],
        ];
    }

    public function attributes(): array
    {
        return [
            'direction' => __('messages.direction'),
            'position' => __('messages.position'),
            'parent_id' => __('messages.parent_id'),
        ];
    }
}
