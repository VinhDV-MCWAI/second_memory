<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Resources\Docs\CategoryResource;
use App\Http\Resources\Docs\EntryDetailResource;
use App\Http\Resources\Docs\EntryResource;
use App\Http\Resources\Docs\SearchResultResource;
use App\Repositories\Management\CategoryMgmtRepository;
use App\Repositories\Management\EntryDescriptionMgmtRepository;
use App\Repositories\Management\EntryMgmtRepository;
use Illuminate\Http\Resources\Json\JsonResource;

class DocsService
{
    public function __construct(
        protected CategoryMgmtRepository $categoryMgmt,
        protected EntryMgmtRepository $entryMgmt,
        protected EntryDescriptionMgmtRepository $entryDescriptionMgmt
    ) {}

    /**
     * Get all displayable categories
     */
    public function getCategories(array $payload): JsonResource
    {
        $categories = $this->categoryMgmt->getDisplayableCategories();

        return CategoryResource::collection($categories);
    }

    /**
     * Get entries by category slug
     */
    public function getEntriesByCategory(string $slug, array $payload): JsonResource
    {
        $entries = $this->entryMgmt->getEntriesByCategorySlug($slug);

        return EntryResource::collection($entries);
    }

    /**
     * Get entry detail with descriptions
     */
    public function getEntryDetail(string $slug, array $payload): JsonResource
    {
        $entry = $this->entryMgmt->getEntryDetailBySlug($slug);

        return new EntryDetailResource($entry);
    }

    /**
     * Global search
     */
    public function search(array $payload): JsonResource
    {
        $query = $payload['q'] ?? '';

        $results = [
            'categories' => $this->categoryMgmt->searchCategories($query),
            'entries' => $this->entryMgmt->searchEntries($query),
            'descriptions' => $this->entryDescriptionMgmt->searchDescriptions($query),
        ];

        return new SearchResultResource($results);
    }
}
