<?php

declare(strict_types=1);

namespace App\Http\Requests\Content\Article;

/**
 * Same fields as on create; without `category_ids` the categories stay as they are.
 */
class UpdateArticleRequest extends StoreArticleRequest {}
