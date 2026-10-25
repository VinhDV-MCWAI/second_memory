<?php

declare(strict_types=1);

namespace App\Repositories\User;

use App\Enums\SavedKind;
use App\Models\Content\Article;
use App\Models\Content\ContentCategory;
use App\Models\User\EndUserSaved;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * What an end user saved (ADR-0020, ADR-0024): articles, and categories as favourites. Only what
 * the public site shows can be saved or listed; a hidden target stays saved and comes back when
 * it is shown again (a deleted one goes with its row: foreign key cascade).
 */
class EndUserSavedRepository
{
    /**
     * @return Collection<int, EndUserSaved>
     */
    public function listFor(int $endUserId, ?SavedKind $kind): Collection
    {
        return EndUserSaved::query()
            ->where('end_user_id', $endUserId)
            ->when($kind, fn (Builder $query) => $query->where('kind', $kind))
            ->where(fn (Builder $query) => $query
                ->whereHas('article', fn (Builder $article) => $article->visible())
                ->orWhereHas('category', fn (Builder $category) => $category->visible()))
            ->with(['article:id,title,slug,public_key', 'category:id,name,slug'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @throws ModelNotFoundException when the article is missing or not on the public site
     */
    public function visibleArticleId(string $publicKey): int
    {
        return (int) Article::query()->visible()->where('public_key', $publicKey)->valueOrFail('article.id');
    }

    /**
     * @throws ModelNotFoundException when the category is missing or not on the public site
     */
    public function visibleCategoryId(string $slug): int
    {
        return (int) ContentCategory::query()->visible()->where('content_category.slug', $slug)->valueOrFail('content_category.id');
    }

    /**
     * @param  array{article_id: int}|array{content_category_id: int}  $target
     */
    public function save(int $endUserId, SavedKind $kind, array $target): void
    {
        EndUserSaved::query()->firstOrCreate(['end_user_id' => $endUserId, 'kind' => $kind, ...$target]);
    }

    /**
     * @param  array{article_id: int}|array{content_category_id: int}  $target
     */
    public function remove(int $endUserId, SavedKind $kind, array $target): void
    {
        EndUserSaved::query()->where(['end_user_id' => $endUserId, 'kind' => $kind, ...$target])->delete();
    }

    /**
     * @throws ModelNotFoundException when the article is not in the end user's read-later list
     */
    public function setReadingAnchor(int $endUserId, int $articleId, ?string $anchor): void
    {
        $saved = EndUserSaved::query()
            ->where(['end_user_id' => $endUserId, 'kind' => SavedKind::READ_LATER, 'article_id' => $articleId])
            ->firstOrFail();
        $saved->update(['reading_anchor' => $anchor, 'read_at' => now()]);
    }
}
