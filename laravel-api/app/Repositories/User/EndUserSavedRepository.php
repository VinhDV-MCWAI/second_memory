<?php

declare(strict_types=1);

namespace App\Repositories\User;

use App\Enums\SavedKind;
use App\Models\Content\Article;
use App\Models\User\EndUserSaved;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * What an end user saved (ADR-0020). Only articles the public site shows can be saved or listed.
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
            ->whereHas('article', fn (Builder $article) => $article->visible())
            ->with('article:id,title,slug,public_key')
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

    public function save(int $endUserId, SavedKind $kind, int $articleId): void
    {
        EndUserSaved::query()->firstOrCreate(['end_user_id' => $endUserId, 'kind' => $kind, 'article_id' => $articleId]);
    }

    public function remove(int $endUserId, SavedKind $kind, int $articleId): void
    {
        EndUserSaved::query()->where(['end_user_id' => $endUserId, 'kind' => $kind, 'article_id' => $articleId])->delete();
    }
}
