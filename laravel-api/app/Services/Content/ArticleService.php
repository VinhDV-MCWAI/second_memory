<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Constants\ContentConst;
use App\Constants\Messages;
use App\Enums\MoveDirection;
use App\Http\Resources\Content\ArticleResource;
use App\Repositories\Content\ArticleRepository;
use App\Repositories\Media\MediaRepository;
use App\Services\AuditedCrudService;
use App\Services\AuditLogger;
use App\Services\Media\FolderAccess;
use Illuminate\Validation\ValidationException;

/**
 * Articles (ADR-0022): a tree of sub-pages at most ARTICLE_DEPTH_MAX levels deep, never a loop.
 */
class ArticleService extends AuditedCrudService
{
    protected string $resource = ArticleResource::class;

    protected string $auditableType = ContentConst::ARTICLE_AUDITABLE_TYPE;

    public function __construct(
        private readonly ArticleRepository $articles,
        private readonly MediaRepository $media,
        private readonly FolderAccess $folderAccess,
        private readonly MarkdownRenderer $renderer,
        AuditLogger $auditLogger,
    ) {
        parent::__construct($articles, $auditLogger);
    }

    /**
     * @throws ValidationException when the tree would get too deep or an embedded file cannot be used
     */
    public function store(array $payload): int
    {
        $this->ensureParent(null, $payload);
        $this->ensureEmbeddable($payload, []);

        return parent::store($payload);
    }

    /**
     * @throws ValidationException when the article would go under itself, the tree too deep, or an
     *                             embedded file cannot be used
     */
    public function update(array $payload): int
    {
        $id = (int) $payload['id'];
        $this->ensureParent($id, $payload);
        $this->ensureEmbeddable($payload, $this->articles->embeddedMediaIds($id));

        return parent::update($payload);
    }

    /**
     * The body as the public page will show it, with signed links for the files (ADR-0022).
     *
     * @return array{html: string, toc: list<array{level: int, id: string, text: string}>}
     */
    public function preview(string $body): array
    {
        return $this->renderer->render($body, MarkdownRenderer::MODE_PREVIEW);
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
     * Newly embedded files must be uploaded, not deleted and in a folder the admin may use; files
     * the article already embeds stay even while they are in the trash.
     *
     * @param  array<string, mixed>  $payload
     * @param  list<int>  $embedded
     *
     * @throws ValidationException
     */
    private function ensureEmbeddable(array $payload, array $embedded): void
    {
        $new = array_values(array_diff(WikiSyntax::fileIds((string) ($payload['body'] ?? '')), $embedded));
        if ($new !== [] && count($this->media->availableIds($new, $this->folderAccess->blockedFolders())) !== count($new)) {
            throw ValidationException::withMessages(['body' => Messages::E0041]);
        }
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
