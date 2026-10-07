<?php

declare(strict_types=1);

namespace App\Repositories\Management;

use App\Models\Management\CategoryMgmt;
use App\Models\Management\EntryDescriptionMgmt;
use App\Models\Management\EntryMgmt;
use App\Repositories\SoftDeleteCrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class EntryMgmtRepository extends SoftDeleteCrudRepository
{
    public function __construct(EntryMgmt $model)
    {
        parent::__construct($model);
    }

    /**
     * Get list with pagination
     */
    public function list(array $payload): LengthAwarePaginator
    {
        $query = $this->model->query()
            ->select([
                'id',
                'name',
                'slug',
                'status',
                'is_display',
                'rank_order',
                'layout_structure',
                'updated_at',
            ])
            ->notDeleted();

        // Apply filters
        $this->applyFilters($query, $payload, [
            'id',
            'status',
            'is_display',
            'rank_order',
        ], [
            'name',
            'slug',
        ]);

        // Apply date range
        $this->applyDateRange($query, $payload);

        // Apply sorting
        $this->applySorting($query, $payload);

        // Pagination
        $perPage = $payload['per_page'] ?? 15;
        $page = $payload['page'] ?? 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get entries by category slug
     */
    public function getEntriesByCategorySlug(string $slug): Collection
    {
        // Get the category by slug
        $category = CategoryMgmt::where('slug', $slug)
            ->where('is_display', true)
            ->where('status', 1)
            ->where('is_delete', false)
            ->first();

        if (! $category || ! $category->layout_structure) {
            return collect();
        }

        // Extract entry IDs from layout_structure
        $entryIds = $this->extractEntryIdsFromLayoutStructure($category->layout_structure);

        if (empty($entryIds)) {
            return collect();
        }

        // Get entries in the order they appear in layout_structure
        return $this->model->query()
            ->select([
                'entry_mgmt.id',
                'entry_mgmt.name',
                'entry_mgmt.slug',
                'entry_mgmt.rank_order',
            ])
            ->whereIn('entry_mgmt.id', $entryIds)
            ->where('entry_mgmt.is_display', true)
            ->where('entry_mgmt.status', 1)
            ->where('entry_mgmt.is_delete', false)
            ->orderByRaw('array_position(ARRAY['.implode(',', $entryIds).'], entry_mgmt.id)')
            ->get();
    }

    /**
     * Extract entry IDs from layout structure recursively
     */
    private function extractEntryIdsFromLayoutStructure(array $layoutStructure): array
    {
        $entryIds = [];

        foreach ($layoutStructure as $item) {
            if (isset($item['entry_mgmt_id'])) {
                $entryIds[] = $item['entry_mgmt_id'];
            }

            if (isset($item['children']) && is_array($item['children'])) {
                $entryIds = array_merge($entryIds, $this->extractEntryIdsFromLayoutStructure($item['children']));
            }
        }

        return array_unique($entryIds);
    }

    public function getEntryDetailBySlug(string $slug): ?Model
    {
        $entry = $this->model->query()
            ->where('slug', $slug)
            ->where('is_display', true)
            ->where('status', 1)
            ->notDeleted()
            ->first();

        if (! $entry) {
            return null;
        }

        if (! empty($entry->layout_structure)) {
            $descIds = $this->extractEntryDescIdsFromLayoutStructure($entry->layout_structure);

            if (! empty($descIds)) {
                $descriptions = EntryDescriptionMgmt::query()
                    ->select([
                        'id',
                        'entry_mgmt_id',
                        'title',
                        'summary',
                        'article',
                        'rank_order',
                    ])
                    ->whereIn('id', $descIds)
                    ->where('is_display', true)
                    ->where('status', 1)
                    ->where('is_delete', false)
                    ->orderByRaw('array_position(ARRAY['.implode(',', $descIds).'], id)')
                    ->get();

                $entry->setRelation('descriptions', $descriptions);

                return $entry;
            }
        }

        // Fallback if no layout_structure or empty descriptions
        $entry->load([
            'descriptions' => function ($query) {
                $query->select([
                    'id',
                    'entry_mgmt_id',
                    'title',
                    'summary',
                    'article',
                    'rank_order',
                ])
                    ->where('is_display', true)
                    ->where('status', 1)
                    ->where('is_delete', false)
                    ->orderBy('rank_order', 'asc');
            },
        ]);

        return $entry;
    }

    /**
     * Extract entry desc IDs from layout structure recursively
     */
    private function extractEntryDescIdsFromLayoutStructure(array $layoutStructure): array
    {
        $descIds = [];

        foreach ($layoutStructure as $item) {
            if (isset($item['entry_desc_id'])) {
                $descIds[] = $item['entry_desc_id'];
            }

            if (isset($item['children']) && is_array($item['children'])) {
                $descIds = array_merge($descIds, $this->extractEntryDescIdsFromLayoutStructure($item['children']));
            }
        }

        return array_unique($descIds);
    }

    /**
     * Search entries
     */
    public function searchEntries(string $query): Collection
    {
        return $this->model->query()
            ->select([
                'id',
                'name',
                'slug',
                'rank_order',
            ])
            ->where('is_display', true)
            ->where('status', 1)
            ->notDeleted()
            ->where('name', 'ILIKE', "%{$query}%")
            ->orderBy('rank_order', 'asc')
            ->limit(10)
            ->get();
    }
}
