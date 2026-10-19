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
            'content_category_id' => ['nullable', 'integer'],
            'is_public' => ['nullable', 'boolean'],
        ];
    }
}
