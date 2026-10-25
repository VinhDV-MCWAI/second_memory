<?php

declare(strict_types=1);

namespace App\Repositories\Content;

use App\Constants\ContentConst;
use App\Enums\MoveDirection;
use App\Models\Content\Article;
use App\Models\Content\CategoryAttachment;
use App\Models\Content\ContentCategory;
use App\Repositories\Concerns\MakesUniqueSlugs;
use App\Repositories\Concerns\MovesAmongSiblings;
use App\Repositories\Concerns\WalksParentTree;
use App\Repositories\CrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;

/**
 * Groups and categories of the public site (ADR-0021, ADR-0022). The slug is set once on create;
 * the order among siblings changes only through move(); a new or re-parented row goes last.
 */
class ContentCategoryRepository extends CrudRepository
{
    use MakesUniqueSlugs;
    use MovesAmongSiblings;
    use WalksParentTree;

    /**
     * Every category with its level (1 at the top) and its place in the tree: ordering by `path`
     * puts each category right after its parent, siblings in their order (depth first).
     */
    private const TREE_PATHS = <<<'SQL'
        WITH RECURSIVE tree(id, depth, path) AS (
            SELECT id, 1, ARRAY[sort_order, id] FROM content_category WHERE parent_id IS NULL
            UNION ALL
            SELECT c.id, tree.depth + 1, tree.path || ARRAY[c.sort_order, c.id] FROM content_category c JOIN tree ON c.parent_id = tree.id
        )
        SELECT id, depth, path FROM tree
        SQL;

    public function __construct(ContentCategory $model)
    {
        parent::__construct($model);
    }

    public function list(array $payload): LengthAwarePaginator
    {
        $query = $this->model->query()
            ->leftJoin('content_category as parent', 'parent.id', '=', 'content_category.parent_id')
            ->leftJoinSub(self::TREE_PATHS, 'tree', 'tree.id', '=', 'content_category.id')
            ->select('content_category.*', 'parent.name as parent_name', 'tree.depth')
            ->withCount(['articles', 'children'])
            ->with('attachments.media');

        foreach (['id', 'parent_id', 'is_public'] as $column) {
            if (isset($payload[$column])) {
                $query->where("content_category.{$column}", $payload[$column]);
            }
        }
        if ($payload['root'] ?? false) {
            $query->whereNull('content_category.parent_id');
        }
        if (isset($payload['name'])) {
            $query->whereRaw('lower(content_category.name) like lower(?)', ['%'.$payload['name'].'%']);
        }

        // Columns are named with the table: the join with the parent repeats them
        $sortable = ['content_category.name', 'content_category.updated_at'];
        $sortBy = 'content_category.'.($payload['sort_by'] ?? '');
        if (in_array($sortBy, $sortable, true)) {
            $this->applySorting($query, [...$payload, 'sort_by' => $sortBy], $sortable);
        } else {
            $query->orderBy('tree.path');
        }

        return $query->paginate($payload['per_page'] ?? ContentConst::PER_PAGE, ['*'], 'page', $payload['page'] ?? 1);
    }

    public function find(int $id): ContentCategory
    {
        /** @var ContentCategory */
        return $this->model->newQuery()->findOrFail($id);
    }

    public function executeStore(array $payload): int
    {
        $payload['slug'] = $this->uniqueSlugFor((string) $payload['name'], 'category');
        $payload['sort_order'] = $this->nextPosition(self::idOrNull($payload['parent_id'] ?? null));
        $id = parent::executeStore($payload);
        $this->syncAttachments($id, $payload);

        return $id;
    }

    public function executeUpdate(array $payload): int
    {
        $category = $this->find((int) $payload['id']);
        if (array_key_exists('parent_id', $payload) && self::idOrNull($payload['parent_id']) !== $category->parent_id) {
            $payload['sort_order'] = $this->nextPosition(self::idOrNull($payload['parent_id']));
        }
        $id = parent::executeUpdate($payload);
        $this->syncAttachments($id, $payload);

        return $id;
    }

    public function move(int $id, MoveDirection $direction): void
    {
        $category = $this->find($id);
        $this->swapWithNeighbour($category, $direction, fn (Builder $siblings) => $siblings->where('parent_id', $category->parent_id));
    }

    /**
     * @param  list<int>  $ids
     */
    public function anyHasChildren(array $ids): bool
    {
        return $this->model->newQuery()->whereIn('parent_id', $ids)->whereNotIn('id', $ids)->exists();
    }

    /**
     * @return list<int>
     */
    public function attachedMediaIds(int $categoryId): array
    {
        return CategoryAttachment::query()->where('content_category_id', $categoryId)->whereNotNull('media_id')->pluck('media_id')->map(intval(...))->all();
    }

    /**
     * Visible groups in order, each with its visible categories, their visible article count and
     * newest visible article: the forum-like home page (ADR-0022).
     *
     * @return Collection<int, ContentCategory>
     */
    public function publicGroups(): Collection
    {
        return ContentCategory::query()
            ->visible()
            ->whereNull('content_category.parent_id')
            ->with(['children' => function (Relation $children): void {
                /** @var HasMany<ContentCategory, ContentCategory> $children */
                $children->where('is_public', true)
                    ->withCount(['articles' => function (Builder $articles): void {
                        /** @var Builder<Article> $articles */
                        $articles->visible();
                    }])
                    ->with(['articles' => function (Relation $articles): void {
                        /** @var BelongsToMany<Article, ContentCategory> $articles */
                        $articles->visible()->latest('article.created_at')->limit(1)
                            ->select(['article.id', 'article.title', 'article.slug', 'article.created_at']);
                    }])
                    ->orderBy('sort_order')->orderBy('id');
            }])
            ->orderBy('content_category.sort_order')
            ->orderBy('content_category.id')
            ->get();
    }

    /**
     * A shown category with its evidence and the shown categories right inside it.
     *
     * @throws ModelNotFoundException when the category is missing or not shown
     */
    public function publicCategoryBySlug(string $slug): ContentCategory
    {
        return ContentCategory::query()
            ->visible()
            ->where('content_category.slug', $slug)
            ->with([
                'attachments.media',
                'children' => function (Relation $children): void {
                    /** @var HasMany<ContentCategory, ContentCategory> $children */
                    $children->where('is_public', true)
                        ->withCount(['articles' => function (Builder $articles): void {
                            /** @var Builder<Article> $articles */
                            $articles->visible();
                        }])
                        ->orderBy('sort_order')->orderBy('id');
                },
            ])
            ->firstOrFail();
    }

    /**
     * Ids of the shown categories below $id, at any level: a category lists their articles too.
     *
     * @return list<int>
     */
    public function visibleDescendantIds(int $id): array
    {
        return array_map(fn (object $row): int => (int) $row->id, DB::select(<<<'SQL'
            WITH RECURSIVE down(id) AS (
                SELECT c.id FROM content_category c WHERE c.parent_id = ? AND c.is_public
                UNION ALL
                SELECT c.id FROM content_category c JOIN down ON c.parent_id = down.id WHERE c.is_public
            )
            SELECT id FROM down
            SQL, [$id]));
    }

    /**
     * The categories above $id, from the top down to its parent: the trail of a category page.
     *
     * @return list<array{name: string, slug: string}>
     */
    public function ancestors(int $id): array
    {
        return array_map(fn (object $row): array => ['name' => (string) $row->name, 'slug' => (string) $row->slug], DB::select(<<<'SQL'
            WITH RECURSIVE up(id, parent_id, depth) AS (
                SELECT id, parent_id, 0 FROM content_category WHERE id = ?
                UNION ALL
                SELECT c.id, c.parent_id, up.depth + 1 FROM content_category c JOIN up ON c.id = up.parent_id
            )
            SELECT c.name, c.slug FROM up JOIN content_category c ON c.id = up.id WHERE up.depth > 0 ORDER BY up.depth DESC
            SQL, [$id]));
    }

    /** Id of a shown category by slug; null when it is missing or not shown */
    public function visibleIdBySlug(string $slug): ?int
    {
        $id = ContentCategory::query()->visible()->where('content_category.slug', $slug)->value('content_category.id');

        return $id === null ? null : (int) $id;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function syncAttachments(int $categoryId, array $payload): void
    {
        if (! array_key_exists('attachments', $payload)) {
            return;
        }
        $kept = [];
        foreach (array_values($payload['attachments'] ?? []) as $position => $item) {
            $fields = [
                'title' => $item['title'],
                'url' => $item['url'] ?? null,
                'media_id' => $item['media_id'] ?? null,
                'note' => $item['note'] ?? null,
                'position' => $position,
            ];
            $existing = isset($item['id'])
                ? CategoryAttachment::query()->where('content_category_id', $categoryId)->find($item['id'])
                : null;
            $attachment = $existing ?? new CategoryAttachment(['content_category_id' => $categoryId]);
            $attachment->fill($fields)->save();
            $kept[] = $attachment->id;
        }
        CategoryAttachment::query()->where('content_category_id', $categoryId)->whereNotIn('id', $kept)->delete();
    }
}
