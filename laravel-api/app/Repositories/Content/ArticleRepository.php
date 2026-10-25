<?php

declare(strict_types=1);

namespace App\Repositories\Content;

use App\Constants\ContentConst;
use App\Enums\ArticleSort;
use App\Enums\MoveDirection;
use App\Models\Content\Article;
use App\Models\Content\ContentCategory;
use App\Models\Media\MediaMgmt;
use App\Repositories\Concerns\MakesUniqueSlugs;
use App\Repositories\Concerns\MovesAmongSiblings;
use App\Repositories\Concerns\WalksParentTree;
use App\Repositories\CrudRepository;
use App\Services\Content\WikiSyntax;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;

/**
 * Articles (ADR-0022). The slug is unique site-wide and set once on create, so `[[slug]]` links
 * keep working. The order among sub-pages of one parent changes only through move(); a new or
 * re-parented article goes last.
 */
class ArticleRepository extends CrudRepository
{
    use MakesUniqueSlugs;
    use MovesAmongSiblings;
    use WalksParentTree;

    public function __construct(Article $model)
    {
        parent::__construct($model);
    }

    public function list(array $payload): LengthAwarePaginator
    {
        $query = $this->model->query()
            ->with(['categories:id,name,slug', 'parent:id,title'])
            ->withCount('children');

        $this->applyFilters($query, $payload, ['id', 'parent_id', 'is_public']);
        if ($payload['root'] ?? false) {
            $query->whereNull('parent_id');
        }
        if (isset($payload['category_id'])) {
            $query->whereHas('categories', fn (Builder $categories) => $categories->whereKey($payload['category_id']));
        }
        if (! empty($payload['category_ids'])) {
            $query->whereHas('categories', fn (Builder $categories) => $categories->whereKey($payload['category_ids']));
        }
        if (isset($payload['title'])) {
            $query->whereRaw('lower(title) like lower(?)', ['%'.$payload['title'].'%']);
        }

        // Sub-pages of one parent read in their own order unless a column is asked for
        $default = isset($payload['parent_id']) ? 'sort_order' : 'updated_at';
        $this->applySorting($query, $payload, ['id', 'title', 'sort_order', 'updated_at'], $default, $default === 'sort_order' ? 'asc' : 'desc');
        $query->orderBy('id');

        return $query->paginate($payload['per_page'] ?? ContentConst::PER_PAGE, ['*'], 'page', $payload['page'] ?? 1);
    }

    public function find(int $id): Article
    {
        /** @var Article */
        return $this->model->newQuery()->findOrFail($id);
    }

    public function executeStore(array $payload): int
    {
        $payload['slug'] = $this->uniqueSlugFor((string) $payload['title'], ContentConst::ARTICLE_SLUG_FALLBACK);
        $payload['sort_order'] = $this->nextPosition(self::idOrNull($payload['parent_id'] ?? null));
        $id = parent::executeStore($payload);
        $this->syncCategories($id, $payload);
        $this->syncEmbeddedFiles($id, $payload);

        return $id;
    }

    public function executeUpdate(array $payload): int
    {
        $article = $this->find((int) $payload['id']);
        if (array_key_exists('parent_id', $payload) && self::idOrNull($payload['parent_id']) !== $article->parent_id) {
            $payload['sort_order'] = $this->nextPosition(self::idOrNull($payload['parent_id']));
        }
        $id = parent::executeUpdate($payload);
        $this->syncCategories($id, $payload);
        $this->syncEmbeddedFiles($id, $payload);

        return $id;
    }

    public function move(int $id, MoveDirection $direction): void
    {
        $article = $this->find($id);
        $this->swapWithNeighbour($article, $direction, fn (Builder $siblings) => $siblings->where('parent_id', $article->parent_id));
    }

    /**
     * @param  list<int>  $ids
     */
    public function anyHasChildren(array $ids): bool
    {
        return $this->model->newQuery()->whereIn('parent_id', $ids)->whereNotIn('id', $ids)->exists();
    }

    /**
     * @throws ModelNotFoundException when the article is missing or not shown
     */
    public function publicArticle(string $slug): Article
    {
        /** @var Article */
        return Article::query()
            ->visible()
            ->where('article.slug', $slug)
            ->select('article.*')
            ->selectRaw(ArticleSort::FAVORITE_COUNT.' AS favorite_count')
            ->with(['categories' => function (Relation $categories): void {
                /** @var BelongsToMany<ContentCategory, Article> $categories */
                $categories->visible()->select(['content_category.id', 'content_category.name', 'content_category.slug']);
            }])
            ->firstOrFail();
    }

    /**
     * Shown articles in any of the categories (a category, or a group and its categories) for its
     * public page (ADR-0022), optionally also in a second category and with the words in the title.
     * Null categories: every shown article (the tabs of the home page, ADR-0024).
     *
     * @param  list<int>|null  $categoryIds
     * @return LengthAwarePaginator<int, Article>
     */
    public function publicArticlesIn(?array $categoryIds, ArticleSort $sort, ?int $alsoCategoryId, ?string $words, int $page, int $perPage = ContentConst::PUBLIC_PER_PAGE): LengthAwarePaginator
    {
        $query = Article::query()
            ->visible()
            ->when($categoryIds !== null, fn (Builder $query) => $query->whereHas('categories', fn (Builder $categories) => $categories->whereKey($categoryIds)))
            ->when($alsoCategoryId !== null, fn (Builder $query) => $query->whereHas('categories', fn (Builder $categories) => $categories->whereKey($alsoCategoryId)))
            ->when($words !== null && trim($words) !== '', fn (Builder $query) => $query->whereRaw('f_unaccent(lower(article.title)) like f_unaccent(lower(?))', ['%'.$this->escapeLike(trim((string) $words)).'%']))
            ->select(['article.id', 'article.title', 'article.slug', 'article.summary', 'article.view_count', 'article.created_at', 'article.updated_at'])
            ->selectRaw(ArticleSort::SAVE_COUNT.' AS save_count')
            ->selectRaw(ArticleSort::FAVORITE_COUNT.' AS favorite_count')
            ->orderByRaw($sort->expression().' DESC')
            // Ties (no views or saves yet) read newest first
            ->orderByDesc('article.created_at')
            ->orderByDesc('article.id');

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Which shown article is in which category: the article counts of the category tree.
     *
     * @return list<object{category_id: int, article_id: int}>
     */
    public function visibleCategoryLinks(): array
    {
        return Article::query()
            ->visible()
            ->join('article_category', 'article_category.article_id', '=', 'article.id')
            ->get(['article_category.content_category_id AS category_id', 'article.id AS article_id'])
            ->map(fn (Article $row): object => (object) ['category_id' => (int) $row->getAttribute('category_id'), 'article_id' => (int) $row->getAttribute('article_id')])
            ->all();
    }

    /** Id of a shown article by slug; null when it is missing or not shown */
    public function visibleIdBySlug(string $slug): ?int
    {
        $id = Article::query()->visible()->where('article.slug', $slug)->value('article.id');

        return $id === null ? null : (int) $id;
    }

    /** One more view; `updated_at` is left alone, a view is not a change */
    public function addView(int $id): void
    {
        DB::table('article')->where('id', $id)->increment('view_count');
    }

    /**
     * Every article under (and including) $rootId, parents before children, siblings in order.
     *
     * @return list<object{id: int, parent_id: int|null, title: string, slug: string, is_public: bool}>
     */
    public function treeRows(int $rootId): array
    {
        return DB::select(<<<'SQL'
            WITH RECURSIVE down(id, path) AS (
                SELECT id, ARRAY[sort_order, id] FROM article WHERE id = ?
                UNION ALL
                SELECT a.id, down.path || ARRAY[a.sort_order, a.id] FROM article a JOIN down ON a.parent_id = down.id
            )
            SELECT a.id, a.parent_id, a.title, a.slug, a.is_public FROM down JOIN article a ON a.id = down.id ORDER BY down.path
            SQL, [$rootId]);
    }

    /** The top of the article's tree: itself when it has no parent */
    public function rootIdOf(int $id): int
    {
        return (int) DB::selectOne(<<<'SQL'
            WITH RECURSIVE up(id, parent_id) AS (
                SELECT id, parent_id FROM article WHERE id = ?
                UNION ALL
                SELECT a.id, a.parent_id FROM article a JOIN up ON a.id = up.parent_id
            )
            SELECT id FROM up WHERE parent_id IS NULL
            SQL, [$id])?->id;
    }

    /**
     * Newest shown articles sharing a category with $article, itself left out.
     *
     * @return Collection<int, Article>
     */
    public function relatedVisible(Article $article, int $limit): Collection
    {
        $categoryIds = $article->categories->modelKeys();

        return Article::query()
            ->visible()
            ->whereKeyNot($article->id)
            ->whereHas('categories', fn (Builder $categories) => $categories->whereKey($categoryIds))
            ->latest('article.created_at')
            ->limit($limit)
            ->get(['article.id', 'article.title', 'article.slug']);
    }

    /**
     * @return list<int>
     */
    public function embeddedMediaIds(int $articleId): array
    {
        return DB::table('article_media')->where('article_id', $articleId)->pluck('media_id')->map(intval(...))->all();
    }

    /** Key of an embedded file on the public site; null when the article does not embed it */
    public function embeddedFileKey(int $articleId, int $mediaId): ?string
    {
        $key = DB::table('article_media')->where(['article_id' => $articleId, 'media_id' => $mediaId])->value('public_id');

        return $key === null ? null : (string) $key;
    }

    /**
     * `article_media` follows the `![[file:{id}]]` lines of the body; a file kept keeps its key.
     *
     * @param  array<string, mixed>  $payload
     */
    private function syncEmbeddedFiles(int $articleId, array $payload): void
    {
        if (! array_key_exists('body', $payload)) {
            return;
        }
        $ids = WikiSyntax::fileIds((string) $payload['body']);
        DB::table('article_media')->where('article_id', $articleId)->whereNotIn('media_id', $ids)->delete();
        $existing = MediaMgmt::query()->whereKey($ids)->pluck('id')->all();
        DB::table('article_media')->insertOrIgnore(array_map(fn ($id): array => ['article_id' => $articleId, 'media_id' => $id], $existing));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function syncCategories(int $articleId, array $payload): void
    {
        if (array_key_exists('category_ids', $payload)) {
            $this->find($articleId)->categories()->sync($payload['category_ids'] ?? []);
        }
    }
}
