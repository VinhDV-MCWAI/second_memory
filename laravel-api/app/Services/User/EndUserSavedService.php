<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Enums\SavedKind;
use App\Http\Resources\User\SavedItemResource;
use App\Repositories\User\EndUserSavedRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Favourites and read-later lists of end users (ADR-0020). Targets are named the way the public
 * site names them: a skill by its slug, an evidence or an article by its public key.
 */
final class EndUserSavedService
{
    public function __construct(private readonly EndUserSavedRepository $saved) {}

    public function list(int $endUserId, ?SavedKind $kind): AnonymousResourceCollection
    {
        return SavedItemResource::collection($this->saved->listFor($endUserId, $kind));
    }

    /**
     * @param  array{kind: string, skill?: string, evidence?: string, article?: string}  $payload
     *
     * @throws ModelNotFoundException when the target is not on the public site
     */
    public function save(int $endUserId, array $payload): void
    {
        $this->saved->save($endUserId, SavedKind::from($payload['kind']), $this->target($payload));
    }

    /**
     * @param  array{kind: string, skill?: string, evidence?: string, article?: string}  $payload
     *
     * @throws ModelNotFoundException when the target is not on the public site
     */
    public function remove(int $endUserId, array $payload): void
    {
        $this->saved->remove($endUserId, SavedKind::from($payload['kind']), $this->target($payload));
    }

    /**
     * @param  array{kind: string, skill?: string, evidence?: string, article?: string}  $payload
     * @return array{skill_id: int}|array{evidence_id: int}|array{article_id: int}
     */
    private function target(array $payload): array
    {
        return match (true) {
            isset($payload['skill']) => ['skill_id' => $this->saved->publicSkillId($payload['skill'])],
            isset($payload['article']) => ['article_id' => $this->saved->visibleArticleId($payload['article'])],
            default => ['evidence_id' => $this->saved->visibleEvidenceId((string) ($payload['evidence'] ?? ''))],
        };
    }
}
