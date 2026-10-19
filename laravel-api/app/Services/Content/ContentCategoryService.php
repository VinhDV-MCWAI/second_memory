<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Constants\ContentConst;
use App\Constants\Messages;
use App\Http\Resources\Content\ContentCategoryResource;
use App\Repositories\Content\ContentCategoryRepository;
use App\Services\AuditedCrudService;
use App\Services\AuditLogger;
use Illuminate\Validation\ValidationException;

class ContentCategoryService extends AuditedCrudService
{
    protected string $resource = ContentCategoryResource::class;

    protected string $auditableType = ContentConst::CATEGORY_AUDITABLE_TYPE;

    public function __construct(private readonly ContentCategoryRepository $categories, AuditLogger $auditLogger)
    {
        parent::__construct($categories, $auditLogger);
    }

    /**
     * @throws ValidationException when a category still has articles (they would be lost)
     */
    public function delete(array $payload): void
    {
        if ($this->categories->anyHasArticles(array_map('intval', $payload['ids'] ?? []))) {
            throw ValidationException::withMessages(['ids' => Messages::E0047]);
        }

        parent::delete($payload);
    }
}
