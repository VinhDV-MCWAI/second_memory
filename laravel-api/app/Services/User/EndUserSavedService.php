<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Enums\SavedKind;
use App\Http\Resources\User\SavedItemResource;
use App\Repositories\User\EndUserSavedRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Favourites and read-later lists of end users (ADR-0020, ADR-0024). Targets are named the way the
 * public site names them: an article by its public key, a category by its slug.
 */
final class EndUserSavedService
{
    public function __construct(private readonly EndUserSavedRepository $saved) {}

    public function list(int $endUserId, ?SavedKind $kind): AnonymousResourceCollection
    {
        return SavedItemResource::collection($this->saved->listFor($endUserId, $kind));
    }

    /**
     * @param  array{kind: string, article?: string|null, category?: string|null}  $payload
     *
     * @throws ModelNotFoundException when the target is not on the public site
     */
    public function save(int $endUserId, array $payload): void
    {
        $this->saved->save($endUserId, SavedKind::from($payload['kind']), $this->target($payload));
    }

    /**
     * @param  array{kind: string, article?: string|null, category?: string|null}  $payload
     *
     * @throws ModelNotFoundException when the target is not on the public site
     */
    public function remove(int $endUserId, array $payload): void
    {
        $this->saved->remove($endUserId, SavedKind::from($payload['kind']), $this->target($payload));
    }

    /**
     * @param  array{article: string, anchor?: string|null}  $payload
     *
     * @throws ModelNotFoundException when the article is not shown or not in the read-later list
     */
    public function setReadingPosition(int $endUserId, array $payload): void
    {
        $this->saved->setReadingAnchor($endUserId, $this->saved->visibleArticleId($payload['article']), $payload['anchor'] ?? null);
    }

    /**
     * @param  array{kind: string, article?: string|null, category?: string|null}  $payload
     * @return array{article_id: int}|array{content_category_id: int}
     */
    private function target(array $payload): array
    {
        return isset($payload['category'])
            ? ['content_category_id' => $this->saved->visibleCategoryId($payload['category'])]
            : ['article_id' => $this->saved->visibleArticleId((string) $payload['article'])];
    }
}
