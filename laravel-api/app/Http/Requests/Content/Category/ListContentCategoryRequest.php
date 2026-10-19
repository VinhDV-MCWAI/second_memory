<?php

declare(strict_types=1);

namespace App\Http\Requests\Content\Category;

use App\Constants\ContentConst;
use App\Http\Requests\ListRequest;

class ListContentCategoryRequest extends ListRequest
{
    protected function filters(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:'.ContentConst::CATEGORY_NAME_MAX],
            'is_public' => ['nullable', 'boolean'],
        ];
    }
}
