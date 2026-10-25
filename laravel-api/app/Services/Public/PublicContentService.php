<?php

declare(strict_types=1);

namespace App\Services\Public;

use App\Constants\ContentConst;
use App\Enums\ArticleSort;
use App\Http\Resources\Public\PublicArticleResource;
use App\Http\Resources\Public\PublicCategoryDetailResource;
use App\Http\Resources\Public\PublicGroupResource;
use App\Models\Content\Article;
use App\Repositories\Content\ArticleRepository;
use App\Repositories\Content\ContentCategoryRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;

/**
 * Read-only public view of groups, categories and articles as a documentation site (ADR-0022).
 * Private or missing → 404.
 *
 * @phpstan-import-type Place from PublicArticleResource
 */
final class PublicContentService
{
    public function __construct(
        private readonly ContentCategoryRepository $categories,
        private readonly ArticleRepository $articles,
    ) {}

    public function home(): AnonymousResourceCollection
    {
        return PublicGroupResource::collection($this->categories->publicGroups());
    }

    /**
     * @param  array{sort?: string|null, also?: string|null, q?: string|null, page?: int|string|null}  $query
     */
    public function category(string $slug, array $query): PublicCategoryDetailResource
    {
        $category = $this->categories->publicCategoryBySlug($slug);
        $also = isset($query['also']) ? $this->categories->visibleIdBySlug($query['also']) : null;
        $articles = $this->articles->publicArticlesIn(
            // A category lists the articles of the shown categories below it too, at any level
            [$category->id, ...$this->categories->visibleDescendantIds($category->id)],
            ArticleSort::tryFrom((string) ($query['sort'] ?? '')) ?? ArticleSort::NEWEST,
            // An unknown second category narrows the list to nothing, not to everything
            isset($query['also']) ? ($also ?? 0) : null,
            $query['q'] ?? null,
            (int) ($query['page'] ?? 1),
        );

        return new PublicCategoryDetailResource($category, $articles, $this->categories->ancestors($category->id));
    }

    /**
     * Counts a view unless the same visitor viewed the article in the last VIEW_WINDOW_MINUTES (ADR-0022).
     *
     * @return array{counted: bool}
     *
     * @throws ModelNotFoundException when the article is missing or not shown
     */
    public function view(string $slug, string $visitor): array
    {
        $id = $this->articles->visibleIdBySlug($slug) ?? throw (new ModelNotFoundException)->setModel(Article::class, [$slug]);
        // add() writes only when the key is missing: atomic in Redis, so two quick reports count once
        $counted = Cache::store(ContentConst::VIEW_CACHE_STORE)
            ->add(ContentConst::VIEW_CACHE_PREFIX.$id.':'.$visitor, true, now()->addMinutes(ContentConst::VIEW_WINDOW_MINUTES));
        if ($counted) {
            $this->articles->addView($id);
        }

        return ['counted' => $counted];
    }

    public function article(string $slug): PublicArticleResource
    {
        $article = $this->articles->publicArticle($slug);

        return new PublicArticleResource($article, $this->place($article));
    }

    /**
     * Where the article sits: a book is the shown part of its tree (a private page hides its
     * subtree); previous and next follow the book's reading order (depth first).
     *
     * @return Place
     */
    private function place(Article $article): array
    {
        $rows = $this->articles->treeRows($this->articles->rootIdOf($article->id));
        $shown = [];
        foreach ($rows as $row) {
            // Parents come first, so a row is shown when it is public and its parent was shown
            if ($row->is_public && ($row->parent_id === null || isset($shown[$row->parent_id]))) {
                $shown[$row->id] = $row;
            }
        }
        if (count($shown) < 2) {
            $related = $this->articles->relatedVisible($article, ContentConst::RELATED_MAX)
                ->map(fn (Article $other): array => ['title' => $other->title, 'slug' => $other->slug])->values()->all();

            return ['book' => null, 'ancestors' => [], 'previous' => null, 'next' => null, 'related' => $related];
        }

        $page = fn (object $row): array => ['title' => (string) $row->title, 'slug' => (string) $row->slug];
        $order = array_values($shown);
        $index = array_search($article->id, array_column($order, 'id'), true);
        $ancestors = [];
        for ($id = $shown[$article->id]->parent_id; $id !== null && isset($shown[$id]); $id = $shown[$id]->parent_id) {
            array_unshift($ancestors, $page($shown[$id]));
        }

        return [
            'book' => $this->node($order[0], $order),
            'ancestors' => $ancestors,
            'previous' => $index > 0 ? $page($order[$index - 1]) : null,
            'next' => $index < count($order) - 1 ? $page($order[$index + 1]) : null,
            'related' => [],
        ];
    }

    /**
     * @param  list<object>  $rows
     * @return array{title: string, slug: string, children: list<mixed>}
     */
    private function node(object $row, array $rows): array
    {
        return [
            'title' => (string) $row->title,
            'slug' => (string) $row->slug,
            'children' => array_values(array_map(
                fn (object $child): array => $this->node($child, $rows),
                array_filter($rows, fn (object $other): bool => $other->parent_id === $row->id),
            )),
        ];
    }
}
