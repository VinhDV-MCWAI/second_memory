<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Constants\ContentConst;
use App\Http\Resources\Content\ArticleResource;
use App\Repositories\Content\ArticleRepository;
use App\Services\AuditedCrudService;
use App\Services\AuditLogger;

class ArticleService extends AuditedCrudService
{
    protected string $resource = ArticleResource::class;

    protected string $auditableType = ContentConst::ARTICLE_AUDITABLE_TYPE;

    public function __construct(ArticleRepository $articles, AuditLogger $auditLogger)
    {
        parent::__construct($articles, $auditLogger);
    }
}
