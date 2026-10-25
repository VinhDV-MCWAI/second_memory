<?php

declare(strict_types=1);

namespace App\Http\Requests\Content\Article;

use App\Constants\ContentConst;
use App\Http\Requests\ListRequest;

class ListArticleRequest extends ListRequest
{
    protected function filters(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:'.ContentConst::ARTICLE_TITLE_MAX],
            'category_id' => ['nullable', 'integer'],
            /** Articles in at least one of these categories */
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer'],
            /** Sub-pages of this article */
            'parent_id' => ['nullable', 'integer'],
            /** Only articles without a parent */
            'root' => ['nullable', 'boolean'],
            'is_public' => ['nullable', 'boolean'],
        ];
    }
}
