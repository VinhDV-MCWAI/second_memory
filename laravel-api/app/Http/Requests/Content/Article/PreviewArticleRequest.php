<?php

declare(strict_types=1);

namespace App\Http\Requests\Content\Article;

use App\Constants\ContentConst;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Markdown to show as the public page would (ADR-0022); nothing is saved.
 */
class PreviewArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['body' => ['present', 'nullable', 'string', 'max:'.ContentConst::ARTICLE_BODY_MAX]];
    }

    public function attributes(): array
    {
        return ['body' => __('messages.body')];
    }
}
