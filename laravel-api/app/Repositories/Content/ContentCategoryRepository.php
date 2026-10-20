<?php

declare(strict_types=1);

namespace App\Repositories\Content;

use App\Constants\ContentConst;
use App\Models\Content\Article;
use App\Models\Content\ContentCategory;
use App\Repositories\Concerns\MakesUniqueSlugs;
use App\Repositories\CrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Categories of the public site (ADR-0021). The slug is set once on create.
 */
class ContentCategoryRepository extends CrudRepository
{
    use MakesUniqueSlugs;

    public function __construct(ContentCategory $model)
    {
        parent::__construct($model);
    }

    public function list(array $payload): LengthAwarePaginator
    {
        $query = $this->model->query()
            ->select(['id', 'name', 'slug', 'description', 'sort_order', 'is_public', 'updated_at'])
            ->withCount('articles');

        $this->applyFilters($query, $payload, ['id', 'is_public']);
        if (isset($payload['name'])) {
            $query->whereRaw('lower(name) like lower(?)', ['%'.$payload['name'].'%']);
        }

        $this->applySorting($query, $payload, ['id', 'name', 'sort_order', 'updated_at'], 'sort_order');
        $query->orderBy('name');

        return $query->paginate($payload['per_page'] ?? ContentConst::PER_PAGE, ['*'], 'page', $payload['page'] ?? 1);
    }

    public function executeStore(array $payload): int
    {
        $payload['slug'] = $this->uniqueSlugFor((string) $payload['name'], 'category');

        return parent::executeStore($payload);
    }

    /**
     * @param  list<int>  $ids
     */
    public function anyHasArticles(array $ids): bool
    {
        return Article::query()->whereIn('content_category_id', $ids)->exists();
    }

    /**
     * @return Collection<int, ContentCategory>
     */
    public function publicCategories(): Collection
    {
        /** @var Collection<int, ContentCategory> */
        return $this->model->newQuery()
            ->where('is_public', true)
            ->withCount(['articles' => fn (Builder $articles) => $articles->where('is_public', true)])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @throws ModelNotFoundException when the category is missing or private
     */
    public function publicCategoryBySlug(string $slug): ContentCategory
    {
        /** @var ContentCategory */
        return $this->model->newQuery()
            ->where('is_public', true)
            ->where('slug', $slug)
            ->with(['articles' => fn ($articles) => $articles->where('is_public', true)
                ->orderBy('sort_order')->orderBy('title')
                ->select(['id', 'content_category_id', 'title', 'slug', 'summary', 'updated_at'])])
            ->firstOrFail();
    }
}
