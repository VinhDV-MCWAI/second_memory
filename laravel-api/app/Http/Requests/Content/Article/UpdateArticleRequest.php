<?php

declare(strict_types=1);

namespace App\Http\Requests\Content\Article;

use App\Constants\ContentConst;
use App\Models\Content\ContentCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The body is Markdown; the public API turns it into safe HTML (ADR-0021).
 */
class UpdateArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content_category_id' => ['required', 'integer', Rule::exists(ContentCategory::class, 'id')],
            'title' => ['required', 'string', 'max:'.ContentConst::ARTICLE_TITLE_MAX],
            'summary' => ['nullable', 'string', 'max:'.ContentConst::ARTICLE_SUMMARY_MAX],
            'body' => ['required', 'string', 'max:'.ContentConst::ARTICLE_BODY_MAX],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:'.ContentConst::SORT_ORDER_MAX],
            'is_public' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'content_category_id' => __('messages.category'),
            'title' => __('messages.title'),
            'summary' => __('messages.summary'),
            'body' => __('messages.body'),
            'sort_order' => __('messages.sort_order'),
            'is_public' => __('messages.is_public'),
        ];
    }
}
