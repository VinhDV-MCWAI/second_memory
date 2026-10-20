<?php

declare(strict_types=1);

namespace App\Http\Requests\Content\Article;

use App\Constants\ContentConst;
use App\Models\Content\Article;
use App\Models\Content\ContentCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The body is Markdown (ADR-0021). An article belongs to any number of categories and may sit
 * under a parent article (ADR-0022).
 */
class StoreArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', Rule::exists(Article::class, 'id')],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'distinct', Rule::exists(ContentCategory::class, 'id')],
            'title' => ['required', 'string', 'max:'.ContentConst::ARTICLE_TITLE_MAX],
            'summary' => ['nullable', 'string', 'max:'.ContentConst::ARTICLE_SUMMARY_MAX],
            'body' => ['required', 'string', 'max:'.ContentConst::ARTICLE_BODY_MAX],
            'is_public' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'parent_id' => __('messages.parent_id'),
            'category_ids' => __('messages.category_ids'),
            'category_ids.*' => __('messages.category'),
            'title' => __('messages.title'),
            'summary' => __('messages.summary'),
            'body' => __('messages.body'),
            'is_public' => __('messages.is_public'),
        ];
    }
}
