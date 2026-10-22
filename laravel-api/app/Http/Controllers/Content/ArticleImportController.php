<?php

declare(strict_types=1);

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\Article\ImportArticleRequest;
use App\Services\Content\ArticleImportService;

class ArticleImportController extends Controller
{
    public function __construct(private readonly ArticleImportService $imports) {}

    /**
     * Sync the complete list of published Obsidian notes into articles (idempotent; `dry_run` only counts)
     *
     * Called by the importer CLI with an API token that has the `article:import` ability, or by the owner's session.
     *
     * @return array{created: int, updated: int, unchanged: int, hidden: int, unknown_categories: list<string>, unknown_links: list<string>}
     */
    public function import(ImportArticleRequest $request): array
    {
        return $this->imports->import($request->validated());
    }
}
