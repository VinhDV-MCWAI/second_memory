<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Constants\ContentConst;
use App\Constants\Messages;
use App\Enums\MoveDirection;
use App\Http\Resources\Content\ContentCategoryResource;
use App\Repositories\Content\ContentCategoryRepository;
use App\Repositories\Media\MediaRepository;
use App\Services\AuditedCrudService;
use App\Services\AuditLogger;
use App\Services\Media\FolderAccess;
use Illuminate\Validation\ValidationException;

/**
 * Groups and categories (ADR-0022): two levels at most, attachments are links or files.
 */
class ContentCategoryService extends AuditedCrudService
{
    protected string $resource = ContentCategoryResource::class;

    protected string $auditableType = ContentConst::CATEGORY_AUDITABLE_TYPE;

    public function __construct(
        private readonly ContentCategoryRepository $categories,
        private readonly MediaRepository $media,
        private readonly FolderAccess $folderAccess,
        AuditLogger $auditLogger,
    ) {
        parent::__construct($categories, $auditLogger);
    }

    /**
     * @throws ValidationException when the parent is not a group, or a file cannot be attached
     */
    public function store(array $payload): int
    {
        $this->ensureParent(null, $payload);
        $this->ensureAttachable($payload, []);

        return parent::store($payload);
    }

    /**
     * @throws ValidationException when the parent is not a group, a group with categories would get
     *                             a parent, or a file cannot be attached
     */
    public function update(array $payload): int
    {
        $id = (int) $payload['id'];
        $this->ensureParent($id, $payload);
        $this->ensureAttachable($payload, $this->categories->attachedMediaIds($id));

        return parent::update($payload);
    }

    /**
     * Articles only lose the link to a deleted category; categories inside a group must go first.
     *
     * @throws ValidationException when a group still has categories
     */
    public function delete(array $payload): void
    {
        if ($this->categories->anyHasChildren(array_map(intval(...), $payload['ids'] ?? []))) {
            throw ValidationException::withMessages(['ids' => Messages::E0047]);
        }

        parent::delete($payload);
    }

    public function move(int $id, MoveDirection $direction): void
    {
        $this->auditedChange($id, fn () => $this->categories->move($id, $direction));
    }

    /**
     * Two levels: the parent must be a group (no parent itself), and a group that holds categories
     * cannot move under another one.
     *
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
        $parent = $this->categories->find($parentId);
        if ($parentId === $id || $parent->parent_id !== null || ($id !== null && $this->categories->hasChildren($id))) {
            throw ValidationException::withMessages(['parent_id' => Messages::E0048]);
        }
    }

    /**
     * Newly attached files must be uploaded, not deleted and in a folder the admin may use. Files
     * already attached stay even while they are in the trash, so a form can post its whole list.
     *
     * @param  array<string, mixed>  $payload
     * @param  list<int>  $attached
     *
     * @throws ValidationException
     */
    private function ensureAttachable(array $payload, array $attached): void
    {
        $sent = array_filter(array_map(fn (array $item): ?int => isset($item['media_id']) ? (int) $item['media_id'] : null, $payload['attachments'] ?? []));
        $new = array_values(array_diff($sent, $attached));
        if ($new !== [] && count($this->media->availableIds($new, $this->folderAccess->blockedFolders())) !== count(array_unique($new))) {
            throw ValidationException::withMessages(['attachments' => Messages::E0041]);
        }
    }
}
