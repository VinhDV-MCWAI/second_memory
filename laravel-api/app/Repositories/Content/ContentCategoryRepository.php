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
use App\Repositories\CrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Groups and categories of the public site (ADR-0021, ADR-0022). The slug is set once on create;
 * the order among siblings changes only through move(); a new or re-parented row goes last.
 */
class ContentCategoryRepository extends CrudRepository
{
    use MakesUniqueSlugs;
    use MovesAmongSiblings;

    /** Groups in their order, each followed by its categories in theirs */
    private const TREE_ORDER = 'coalesce(parent.sort_order, content_category.sort_order), coalesce(parent.id, content_category.id), content_category.parent_id IS NOT NULL, content_category.sort_order, content_category.id';

    public function __construct(ContentCategory $model)
    {
        parent::__construct($model);
    }

    public function list(array $payload): LengthAwarePaginator
    {
        $query = $this->model->query()
            ->leftJoin('content_category as parent', 'parent.id', '=', 'content_category.parent_id')
            ->select('content_category.*', 'parent.name as parent_name')
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
            $query->orderByRaw(self::TREE_ORDER);
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

    public function hasChildren(int $id): bool
    {
        return $this->model->newQuery()->where('parent_id', $id)->exists();
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
     * Visible groups and categories in tree order, with their visible articles counted.
     *
     * @return Collection<int, ContentCategory>
     */
    public function publicCategories(): Collection
    {
        return ContentCategory::query()
            ->visible()
            ->leftJoin('content_category as parent', 'parent.id', '=', 'content_category.parent_id')
            ->select('content_category.*', 'parent.name as parent_name')
            ->withCount(['articles' => function (Builder $articles): void {
                /** @var Builder<Article> $articles */
                $articles->visible();
            }])
            ->orderByRaw(self::TREE_ORDER)
            ->get();
    }

    /**
     * @throws ModelNotFoundException when the category is missing or not shown
     */
    public function publicCategoryBySlug(string $slug): ContentCategory
    {
        return ContentCategory::query()
            ->visible()
            ->where('content_category.slug', $slug)
            ->with([
                'parent:id,name,slug',
                'attachments.media',
                'articles' => function (Relation $articles): void {
                    /** @var BelongsToMany<Article, ContentCategory> $articles */
                    $articles->visible()
                        ->orderByDesc('article.updated_at')->orderBy('article.id')
                        ->select(['article.id', 'article.title', 'article.slug', 'article.summary', 'article.updated_at']);
                },
            ])
            ->firstOrFail();
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
