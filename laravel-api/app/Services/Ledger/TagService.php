<?php

declare(strict_types=1);

namespace App\Services\Ledger;

use App\Http\Resources\Ledger\TagResource;
use App\Repositories\Ledger\TagRepository;
use App\Services\AuditedCrudService;
use App\Services\AuditLogger;

class TagService extends AuditedCrudService
{
    protected string $resource = TagResource::class;

    protected string $auditableType = 'tag';

    public function __construct(TagRepository $tags, AuditLogger $auditLogger)
    {
        parent::__construct($tags, $auditLogger);
    }
}
