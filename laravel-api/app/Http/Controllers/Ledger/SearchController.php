<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ledger;

use App\Enums\SearchMatch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ledger\Search\SearchRequest;
use App\Services\Ledger\SearchService;

class SearchController extends Controller
{
    public function __construct(private readonly SearchService $search) {}

    /**
     * Search skills, goals, evidence and the names of files and folders (accents and case ignored; typo fallback when nothing matches).
     * `files` is empty for an admin without `media.list`, and never holds the trash or locked folders the admin may not use
     *
     * @return array{match: SearchMatch, skills: list<array{id: int, title: string, snippet: string|null}>, goals: list<array{id: int, title: string, snippet: string|null}>, evidence: list<array{id: int, title: string, snippet: string|null}>, files: list<array{id: int, is_file: bool, name: string, path: string, size: int|null, thumbnail_url: string|null}>}
     */
    public function search(SearchRequest $request): array
    {
        return $this->search->search($request->validated('q'));
    }
}
