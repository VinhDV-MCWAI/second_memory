<?php

declare(strict_types=1);

namespace App\Repositories\Content;

use App\Constants\ContentConst;
use App\Models\Content\Article;
use App\Repositories\Concerns\MakesUniqueSlugs;
use App\Repositories\CrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Articles of the public site (ADR-0021). The slug is unique within its category; moving an
 * article to a category where its slug is taken gives it a numbered one.
 */
class ArticleRepository extends CrudRepository
{
    use MakesUniqueSlugs;

    public function __construct(Article $model)
    {
        parent::__construct($model);
    }

    public function list(array $payload): LengthAwarePaginator
    {
        $query = $this->model->query()
            ->select(['id', 'content_category_id', 'title', 'slug', 'summary', 'body', 'sort_order', 'is_public', 'updated_at'])
            ->with('category:id,name,slug');

        $this->applyFilters($query, $payload, ['id', 'content_category_id', 'is_public']);
        if (isset($payload['title'])) {
            $query->whereRaw('lower(title) like lower(?)', ['%'.$payload['title'].'%']);
        }

        $this->applySorting($query, $payload, ['id', 'title', 'content_category_id', 'sort_order', 'updated_at'], 'updated_at', 'desc');

        return $query->paginate($payload['per_page'] ?? ContentConst::PER_PAGE, ['*'], 'page', $payload['page'] ?? 1);
    }

    public function executeStore(array $payload): int
    {
        $payload['slug'] = $this->slugIn((int) $payload['content_category_id'], (string) $payload['title']);

        return parent::executeStore($payload);
    }

    public function executeUpdate(array $payload): int
    {
        /** @var Article $article */
        $article = $this->findForUpdate($payload['id']);
        $categoryId = (int) ($payload['content_category_id'] ?? $article->content_category_id);
        if ($categoryId !== $article->content_category_id) {
            $payload['slug'] = $this->slugIn($categoryId, $article->slug, $article->id);
        }

        return parent::executeUpdate($payload);
    }

    /**
     * @throws ModelNotFoundException when the article or its category is missing or private
     */
    public function publicArticle(string $categorySlug, string $slug): Article
    {
        /** @var Article */
        return Article::query()
            ->visible()
            ->where('article.slug', $slug)
            ->whereHas('category', fn (Builder $category) => $category->where('slug', $categorySlug))
            ->with('category:id,name,slug')
            ->firstOrFail();
    }

    private function slugIn(int $categoryId, string $text, ?int $ignoreId = null): string
    {
        return $this->uniqueSlugFor($text, 'article', fn (Builder $query) => $query->where('content_category_id', $categoryId), $ignoreId);
    }
}
