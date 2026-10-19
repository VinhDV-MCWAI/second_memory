<?php

declare(strict_types=1);

namespace App\Services\Public;

use App\Http\Resources\Public\PublicArticleResource;
use App\Http\Resources\Public\PublicCategoryDetailResource;
use App\Http\Resources\Public\PublicCategoryResource;
use App\Repositories\Content\ArticleRepository;
use App\Repositories\Content\ContentCategoryRepository;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Read-only public view of categories and articles (ADR-0021). Private or missing → 404.
 */
final class PublicContentService
{
    public function __construct(
        private readonly ContentCategoryRepository $categories,
        private readonly ArticleRepository $articles,
    ) {}

    public function categories(): AnonymousResourceCollection
    {
        return PublicCategoryResource::collection($this->categories->publicCategories());
    }

    public function category(string $slug): PublicCategoryDetailResource
    {
        return new PublicCategoryDetailResource($this->categories->publicCategoryBySlug($slug));
    }

    public function article(string $categorySlug, string $slug): PublicArticleResource
    {
        return new PublicArticleResource($this->articles->publicArticle($categorySlug, $slug));
    }
}
