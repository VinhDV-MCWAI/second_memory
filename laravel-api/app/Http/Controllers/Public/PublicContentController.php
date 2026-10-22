<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\PublicCategoryRequest;
use App\Http\Resources\Public\PublicArticleResource;
use App\Http\Resources\Public\PublicCategoryDetailResource;
use App\Http\Resources\Public\PublicGroupResource;
use App\Services\Public\PublicContentService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * No login. GET only, rate-limited per IP (ADR-0021, ADR-0022).
 */
class PublicContentController extends Controller
{
    public function __construct(private readonly PublicContentService $content) {}

    /**
     * The home page: public groups in the owner's order, each with its public categories, their article count and newest article
     *
     * @unauthenticated
     *
     * @return AnonymousResourceCollection<int, PublicGroupResource>
     */
    public function home(): AnonymousResourceCollection
    {
        return $this->content->home();
    }

    /**
     * One public group or category: description as safe HTML, evidence, its categories (a group), and a page of its public articles
     * (`sort`: newest | updated; `also`: only articles in that category too; `q`: words of the title). Private (or in a private group)
     * or missing → 404
     *
     * @unauthenticated
     */
    public function category(PublicCategoryRequest $request, string $slug): PublicCategoryDetailResource
    {
        /** @var array{sort?: string|null, also?: string|null, q?: string|null, page?: int|string|null} $query */
        $query = $request->validated();

        return $this->content->category($slug, $query);
    }

    /**
     * One public article: body as safe HTML, table of contents, categories, its book (tree, way up, previous / next) or related
     * articles; private (or under a private article) or missing → 404
     *
     * @unauthenticated
     */
    public function article(string $slug): PublicArticleResource
    {
        return $this->content->article($slug);
    }
}
