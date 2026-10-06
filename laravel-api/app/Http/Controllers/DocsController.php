<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Docs\GetCategoriesRequest;
use App\Http\Requests\Docs\GetEntriesByCategoryRequest;
use App\Http\Requests\Docs\GetEntryDetailRequest;
use App\Http\Requests\Docs\SearchDocsRequest;
use App\Services\DocsService;
use Illuminate\Http\Resources\Json\JsonResource;

class DocsController extends Controller
{
    public function __construct(
        protected DocsService $docsService
    ) {}

    /**
     * Get all displayable categories for documentation
     */
    public function getCategories(GetCategoriesRequest $request): JsonResource
    {
        return $this->docsService->getCategories($request->validated());
    }

    /**
     * Get entries by category slug
     */
    public function getEntriesByCategory(GetEntriesByCategoryRequest $request, string $slug): JsonResource
    {
        return $this->docsService->getEntriesByCategory($slug, $request->validated());
    }

    /**
     * Get entry detail with descriptions
     */
    public function getEntryDetail(GetEntryDetailRequest $request, string $slug): JsonResource
    {
        return $this->docsService->getEntryDetail($slug, $request->validated());
    }

    /**
     * Global search across categories, entries, and descriptions
     */
    public function search(SearchDocsRequest $request): JsonResource
    {
        return $this->docsService->search($request->validated());
    }
}
