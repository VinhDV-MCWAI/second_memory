<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\SearchFoundIn;
use App\Enums\SearchMatch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Content\Search\SearchRequest;
use App\Http\Requests\Public\PublicArticleListRequest;
use App\Http\Requests\Public\PublicCategoryRequest;
use App\Http\Requests\Public\PublicViewRequest;
use App\Http\Resources\Public\PublicArticleListResource;
use App\Http\Resources\Public\PublicArticleResource;
use App\Http\Resources\Public\PublicCategoryDetailResource;
use App\Http\Resources\Public\PublicGroupResource;
use App\Services\Content\SearchService;
use App\Services\Public\PublicContentService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * No login. GET only, rate-limited per IP (ADR-0021, ADR-0022).
 */
class PublicContentController extends Controller
{
    public function __construct(
        private readonly PublicContentService $content,
        private readonly SearchService $search,
    ) {}

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
     * Every public category in tree order (parents first, siblings in the owner's order), with its level, the number of public
     * articles in it and below it, its view count and favourites: the category tree of the reading page and the home page
     *
     * @unauthenticated
     *
     * @return list<array{name: string, slug: string, parent_slug: string|null, depth: int, article_count: int, view_count: int, favorite_count: int}>
     */
    public function categories(): array
    {
        return $this->content->categories();
    }

    /**
     * A page of every public article in the order asked for (`sort`; `per_page` up to 100): the tabs of the home page
     *
     * @unauthenticated
     */
    public function articles(PublicArticleListRequest $request): PublicArticleListResource
    {
        /** @var array{sort?: string|null, page?: int|string|null, per_page?: int|string|null} $query */
        $query = $request->validated();

        return $this->content->articles($query);
    }

    /**
     * One public group or category: description as safe HTML, evidence, its categories (a group), and a page of its public articles
     * (`sort`; `also`: only articles in that category too; `q`: words of the title; `per_page` up to 100). Private (or in a private
     * group) or missing → 404
     *
     * @unauthenticated
     */
    public function category(PublicCategoryRequest $request, string $slug): PublicCategoryDetailResource
    {
        /** @var array{sort?: string|null, also?: string|null, q?: string|null, page?: int|string|null, per_page?: int|string|null} $query */
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

    /**
     * Report that the public site showed an article: counted once per visitor (a hash the site makes from IP and browser,
     * never stored) per 30 minutes; `counted` is false for a repeat. Private or missing → 404
     *
     * @unauthenticated
     *
     * @return array{counted: bool}
     */
    public function view(PublicViewRequest $request, string $slug): array
    {
        return $this->content->view($slug, (string) $request->validated('visitor'));
    }

    /**
     * Report that a reader stayed on a category page (the public site's rule): counted once per visitor per 30 minutes, like
     * articles. Private or missing → 404
     *
     * @unauthenticated
     *
     * @return array{counted: bool}
     */
    public function viewCategory(PublicViewRequest $request, string $slug): array
    {
        return $this->content->viewCategory($slug, (string) $request->validated('visitor'));
    }

    /**
     * Search the shown articles (title, summary, body) and categories (name, description); accents and case ignored,
     * typo fallback (`match: fuzzy`) when nothing matches. Articles say whether the words are in the title / summary or only in
     * the body (`found_in`) and carry the passage of the body around them (`snippet`, safe HTML with `<mark>`); `sections` are
     * the headings of the found articles that hold every word, with their anchor
     *
     * @unauthenticated
     *
     * @return array{match: SearchMatch, articles: list<array{title: string, slug: string, summary: string|null, found_in: SearchFoundIn, snippet: string|null}>, categories: list<array{name: string, slug: string, group: string|null}>, sections: list<array{article_title: string, article_slug: string, heading: string, anchor: string}>}
     */
    public function search(SearchRequest $request): array
    {
        return $this->search->publicSearch((string) $request->validated('q'));
    }
}
