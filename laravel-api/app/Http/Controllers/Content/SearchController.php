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
     * Search articles (title, summary, body), categories (name, description) and the names of files and folders; accents
     * and case ignored, typo fallback (`match: fuzzy`) when nothing matches. Private articles and categories are included.
     * `files` is empty for an admin without `media.list`, and never holds the trash or locked folders the admin may not use
     *
     * @return array{match: SearchMatch, articles: list<array{id: int, title: string, slug: string, snippet: string|null, is_public: bool}>, categories: list<array{id: int, name: string, slug: string, group: string|null, is_public: bool}>, files: list<array{id: int, is_file: bool, name: string, path: string, size: int|null, thumbnail_url: string|null}>}
     */
    public function search(SearchRequest $request): array
    {
        return $this->search->search((string) $request->validated('q'));
    }
}
