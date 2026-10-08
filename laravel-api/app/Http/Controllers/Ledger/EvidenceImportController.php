<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ledger;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ledger\EvidenceImport\ImportEvidenceRequest;
use App\Services\Ledger\EvidenceImportService;

class EvidenceImportController extends Controller
{
    public function __construct(private readonly EvidenceImportService $imports) {}

    /**
     * Sync the complete list of published Obsidian notes (idempotent; `dry_run` only counts)
     *
     * Called by the importer CLI with an API token that has the `evidence:import` ability, or by the owner's session.
     *
     * @return array{created: int, updated: int, unchanged: int, hidden: int, unknown_skills: list<string>}
     */
    public function import(ImportEvidenceRequest $request): array
    {
        return $this->imports->import($request->validated());
    }
}
