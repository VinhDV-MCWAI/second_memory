<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Constants\ContentConst;
use App\Constants\Messages;
use App\Enums\MoveDirection;
use App\Http\Resources\Content\ArticleResource;
use App\Repositories\Content\ArticleRepository;
use App\Services\AuditedCrudService;
use App\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

/**
 * Articles (ADR-0022): a tree of sub-pages at most ARTICLE_DEPTH_MAX levels deep, never a loop.
 */
class ArticleService extends AuditedCrudService
{
    protected string $resource = ArticleResource::class;

    protected string $auditableType = ContentConst::ARTICLE_AUDITABLE_TYPE;

    public function __construct(private readonly ArticleRepository $articles, AuditLogger $auditLogger)
    {
        parent::__construct($articles, $auditLogger);
    }

    /**
     * @throws ValidationException when the tree would get too deep
     */
    public function store(array $payload): int
    {
        $this->ensureParent(null, $payload);

        return parent::store($payload);
    }

    /**
     * @throws ValidationException when the article would go under itself or the tree too deep
     */
    public function update(array $payload): int
    {
        $this->ensureParent((int) $payload['id'], $payload);

        return parent::update($payload);
    }

    /**
     * @throws ValidationException when an article still has sub-pages that are not deleted with it
     */
    public function delete(array $payload): void
    {
        if ($this->articles->anyHasChildren(array_map(intval(...), $payload['ids'] ?? []))) {
            throw ValidationException::withMessages(['ids' => Messages::E0051]);
        }

        parent::delete($payload);
    }

    public function move(int $id, MoveDirection $direction): void
    {
        $this->auditedChange($id, fn () => $this->articles->move($id, $direction));
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws ValidationException
     */
    private function ensureParent(?int $id, array $payload): void
    {
        $parentId = isset($payload['parent_id']) ? (int) $payload['parent_id'] : null;
        if ($parentId === null) {
            return;
        }
        if ($id !== null && $this->articles->isSelfOrDescendant($parentId, $id)) {
            throw ValidationException::withMessages(['parent_id' => Messages::E0049]);
        }
        $height = $id === null ? 1 : $this->articles->height($id);
        if ($this->articles->depth($parentId) + $height > ContentConst::ARTICLE_DEPTH_MAX) {
            throw ValidationException::withMessages(['parent_id' => Messages::E0050]);
        }
    }
}
