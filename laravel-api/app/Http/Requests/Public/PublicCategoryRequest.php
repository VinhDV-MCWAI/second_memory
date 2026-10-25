<?php

declare(strict_types=1);

namespace App\Http\Requests\Public;

use App\Constants\ContentConst;
use App\Enums\ArticleSort;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The article list of a category page (ADR-0022): order, a second category to narrow it, a title
 * search, a page.
 */
class PublicCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sort' => ['nullable', Rule::enum(ArticleSort::class)],
            /** Slug of another category: only articles in both */
            'also' => ['nullable', 'string', 'max:'.ContentConst::CATEGORY_NAME_MAX],
            /** Words of the title */
            'q' => ['nullable', 'string', 'max:'.ContentConst::ARTICLE_TITLE_MAX],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.ContentConst::PUBLIC_PER_PAGE_MAX],
        ];
    }
}
