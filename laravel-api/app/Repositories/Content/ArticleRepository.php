<?php

declare(strict_types=1);

namespace App\Repositories\Content;

use App\Constants\ContentConst;
use App\Enums\MoveDirection;
use App\Models\Content\Article;
use App\Models\Content\ContentCategory;
use App\Repositories\Concerns\MakesUniqueSlugs;
use App\Repositories\Concerns\MovesAmongSiblings;
use App\Repositories\CrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
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
        $payload['slug'] = $this->uniqueSlugFor((string) $payload['title'], 'article');
        $payload['sort_order'] = $this->nextPosition(self::idOrNull($payload['parent_id'] ?? null));
        $id = parent::executeStore($payload);
        $this->syncCategories($id, $payload);

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

        return $id;
    }

    public function move(int $id, MoveDirection $direction): void
    {
        $article = $this->find($id);
        $this->swapWithNeighbour($article, $direction, fn (Builder $siblings) => $siblings->where('parent_id', $article->parent_id));
    }

    /**
     * Levels from the root down to this article, the root being 1.
     */
    public function depth(int $id): int
    {
        return (int) DB::selectOne(<<<'SQL'
            WITH RECURSIVE up(id, parent_id, depth) AS (
                SELECT id, parent_id, 1 FROM article WHERE id = ?
                UNION ALL
                SELECT a.id, a.parent_id, up.depth + 1 FROM article a JOIN up ON a.id = up.parent_id
            )
            SELECT max(depth) AS depth FROM up
            SQL, [$id])?->depth;
    }

    /**
     * Levels of the subtree under this article, the article itself being 1.
     */
    public function height(int $id): int
    {
        return (int) DB::selectOne(<<<'SQL'
            WITH RECURSIVE down(id, height) AS (
                SELECT id, 1 FROM article WHERE id = ?
                UNION ALL
                SELECT a.id, down.height + 1 FROM article a JOIN down ON a.parent_id = down.id
            )
            SELECT max(height) AS height FROM down
            SQL, [$id])?->height;
    }

    /**
     * Whether $candidate is $id itself or one of its sub-pages, at any level.
     */
    public function isSelfOrDescendant(int $candidate, int $id): bool
    {
        return DB::selectOne(<<<'SQL'
            WITH RECURSIVE down(id) AS (
                SELECT id FROM article WHERE id = ?
                UNION ALL
                SELECT a.id FROM article a JOIN down ON a.parent_id = down.id
            )
            SELECT EXISTS (SELECT 1 FROM down WHERE id = ?) AS found
            SQL, [$id, $candidate])?->found === true;
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
            ->with(['categories' => function (Relation $categories): void {
                /** @var BelongsToMany<ContentCategory, Article> $categories */
                $categories->visible()->select(['content_category.id', 'content_category.name', 'content_category.slug']);
            }])
            ->firstOrFail();
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
