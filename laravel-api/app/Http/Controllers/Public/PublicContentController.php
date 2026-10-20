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
 * No login. GET only, rate-limited per IP (ADR-0021, ADR-0022).
 */
class PublicContentController extends Controller
{
    public function __construct(private readonly PublicContentService $content) {}

    /**
     * Public groups and categories of the home page: each group followed by its categories, in the owner's order
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
     * One public category: description as safe HTML, evidence, public articles; private (or in a private group) or missing → 404
     *
     * @unauthenticated
     */
    public function category(string $slug): PublicCategoryDetailResource
    {
        return $this->content->category($slug);
    }

    /**
     * One public article, its Markdown body as safe HTML; private (or under a private article) or missing → 404
     *
     * @unauthenticated
     */
    public function article(string $slug): PublicArticleResource
    {
        return $this->content->article($slug);
    }
}
