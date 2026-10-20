<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Enums\SavedKind;
use App\Http\Resources\User\SavedItemResource;
use App\Repositories\User\EndUserSavedRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Favourites and read-later lists of end users (ADR-0020). Articles are named the way the public
 * site names them: by their public key.
 */
final class EndUserSavedService
{
    public function __construct(private readonly EndUserSavedRepository $saved) {}

    public function list(int $endUserId, ?SavedKind $kind): AnonymousResourceCollection
    {
        return SavedItemResource::collection($this->saved->listFor($endUserId, $kind));
    }

    /**
     * @param  array{kind: string, article: string}  $payload
     *
     * @throws ModelNotFoundException when the article is not on the public site
     */
    public function save(int $endUserId, array $payload): void
    {
        $this->saved->save($endUserId, SavedKind::from($payload['kind']), $this->saved->visibleArticleId($payload['article']));
    }

    /**
     * @param  array{kind: string, article: string}  $payload
     *
     * @throws ModelNotFoundException when the article is not on the public site
     */
    public function remove(int $endUserId, array $payload): void
    {
        $this->saved->remove($endUserId, SavedKind::from($payload['kind']), $this->saved->visibleArticleId($payload['article']));
    }
}
