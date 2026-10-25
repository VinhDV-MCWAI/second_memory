<?php

declare(strict_types=1);

namespace App\Http\Requests\Public;

use App\Constants\ContentConst;
use App\Enums\ArticleSort;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Every shown article, for the tabs of the home page (ADR-0024): order and page.
 */
class PublicArticleListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sort' => ['nullable', Rule::enum(ArticleSort::class)],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.ContentConst::PUBLIC_PER_PAGE_MAX],
        ];
    }
}
