<?php

declare(strict_types=1);

namespace App\Http\Controllers\Content;

use App\Enums\SearchMatch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Content\Search\SearchRequest;
use App\Services\Content\SearchService;

class SearchController extends Controller
{
    public function __construct(private readonly SearchService $search) {}

    /**
     * Search the names of files and folders (accents and case ignored; typo fallback when nothing matches).
     * `files` is empty for an admin without `media.list`, and never holds the trash or locked folders the admin may not use
     *
     * @return array{match: SearchMatch, files: list<array{id: int, is_file: bool, name: string, path: string, size: int|null, thumbnail_url: string|null}>}
     */
    public function search(SearchRequest $request): array
    {
        return $this->search->search($request->validated('q'));
    }
}
