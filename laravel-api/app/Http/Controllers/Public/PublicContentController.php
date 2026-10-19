<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Public\PublicArticleResource;
use App\Http\Resources\Public\PublicCategoryDetailResource;
use App\Http\Resources\Public\PublicCategoryResource;
use App\Services\Public\PublicContentService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * No login. GET only, rate-limited per IP, like the public skills (ADR-0021).
 */
class PublicContentController extends Controller
{
    public function __construct(private readonly PublicContentService $content) {}

    /**
     * Public categories of the home page, in the owner's order
     *
     * @unauthenticated
     *
     * @return AnonymousResourceCollection<int, PublicCategoryResource>
     */
    public function categories(): AnonymousResourceCollection
    {
        return $this->content->categories();
    }

    /**
     * One public category with its public articles; private or missing → 404
     *
     * @unauthenticated
     */
    public function category(string $slug): PublicCategoryDetailResource
    {
        return $this->content->category($slug);
    }

    /**
     * One public article, its Markdown body as safe HTML; private (or private category) or missing → 404
     *
     * @unauthenticated
     */
    public function article(string $category, string $slug): PublicArticleResource
    {
        return $this->content->article($category, $slug);
    }
}
