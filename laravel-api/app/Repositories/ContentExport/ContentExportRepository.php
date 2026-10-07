<?php

declare(strict_types=1);

namespace App\Repositories\ContentExport;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Raw reads of the legacy content tables for the one-off Markdown export.
 * Uses the query builder (not the Eloquent models) so the export reads exactly
 * what is stored, including rows that are hidden or drafts.
 */
final class ContentExportRepository
{
    /**
     * @return Collection<int, stdClass>
     */
    public function categories(bool $includeDeleted): Collection
    {
        return $this->rows('category_mgmt', $includeDeleted);
    }

    /**
     * @return Collection<int, stdClass>
     */
    public function entries(bool $includeDeleted): Collection
    {
        return $this->rows('entry_mgmt', $includeDeleted);
    }

    /**
     * @return Collection<int, stdClass>
     */
    public function descriptions(bool $includeDeleted): Collection
    {
        return $this->rows('entry_description_mgmt', $includeDeleted);
    }

    /**
     * @return Collection<int, stdClass>
     */
    private function rows(string $table, bool $includeDeleted): Collection
    {
        return DB::table($table)
            ->when(! $includeDeleted, fn ($query) => $query->where('is_delete', false))
            ->orderBy('rank_order')
            ->orderBy('id')
            ->get();
    }
}
