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
     * Search skills, goals and evidence (accents and case ignored; typo fallback when nothing matches)
     *
     * @return array{match: SearchMatch, skills: list<array{id: int, title: string, snippet: string|null}>, goals: list<array{id: int, title: string, snippet: string|null}>, evidence: list<array{id: int, title: string, snippet: string|null}>}
     */
    public function search(SearchRequest $request): array
    {
        return $this->search->search($request->validated('q'));
    }
}
