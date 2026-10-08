<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\IsDelete;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

abstract class BaseRepository
{
    public function __construct(protected Model $model) {}

    /**
     * Apply dynamic filters to query based on payload
     *
     * @param  Builder  $query
     * @param  array  $exactMatchFields  Fields that should use exact match (=)
     * @param  array  $likeFields  Fields that should use LIKE search
     * @return void
     *
     * Fields are payload keys; use `'payload_key' => 'table.column'` to filter
     * a qualified column (e.g. on joined queries).
     */
    protected function applyFilters($query, array $payload, array $exactMatchFields = [], array $likeFields = []): void
    {
        foreach ($exactMatchFields as $key => $column) {
            $key = is_int($key) ? $column : $key;
            if (isset($payload[$key])) {
                $query->where($column, $payload[$key]);
            }
        }

        foreach ($likeFields as $key => $column) {
            $key = is_int($key) ? $column : $key;
            if (isset($payload[$key])) {
                $query->where($column, 'like', '%'.$payload[$key].'%');
            }
        }
    }

    /**
     * Apply date range filter to query
     *
     * @param  Builder  $query
     * @param  string  $dateField  The field to filter on (default: 'updated_at')
     */
    protected function applyDateRange($query, array $payload, string $dateField = 'updated_at'): void
    {
        if (isset($payload['from_date'])) {
            $fromDate = Carbon::parse($payload['from_date']);
            $query->whereDate($dateField, '>=', $fromDate);
        }

        if (isset($payload['to_date'])) {
            $toDate = Carbon::parse($payload['to_date']);
            $query->whereDate($dateField, '<=', $toDate);
        }
    }

    /**
     * Order by `sort_by` when it is one of the sortable columns, otherwise by the default.
     * The allow-list replaces a per-request Schema::hasColumn() query (API-04) and keeps
     * unknown or hidden columns out of ORDER BY.
     *
     * @param  Builder  $query
     * @param  list<string>  $sortable
     */
    protected function applySorting($query, array $payload, array $sortable, string $defaultSortBy = 'id', string $defaultSortOrder = 'asc'): void
    {
        $sortBy = in_array($payload['sort_by'] ?? null, $sortable, true) ? $payload['sort_by'] : $defaultSortBy;
        $sortOrder = $payload['sort_order'] ?? $defaultSortOrder;
        $sortOrder = in_array(strtolower((string) $sortOrder), ['asc', 'desc'], true) ? $sortOrder : $defaultSortOrder;

        $query->orderBy($sortBy, $sortOrder);
    }

    /**
     * Validate that foreign key references exist
     *
     * @param  array  $foreignKeys  Array of ['field' => 'ModelClass']
     *
     * @throws ModelNotFoundException
     */
    protected function validateForeignKeys(array $foreignKeys, array $payload): void
    {
        foreach ($foreignKeys as $field => $modelClass) {
            if (isset($payload[$field])) {
                $exists = $modelClass::where('id', $payload[$field])
                    ->where('is_delete', IsDelete::FALSE->value)
                    ->exists();

                if (! $exists) {
                    throw new ModelNotFoundException(
                        ucfirst(str_replace('_id', '', $field)).' not found'
                    );
                }
            }
        }
    }

    /**
     * Check if records can be deleted (no dependent records)
     *
     * @param  array  $relationships  Array of relationship names to check
     *
     * @throws \LogicException
     */
    protected function checkCanDelete(array $ids, array $relationships = []): void
    {
        foreach ($relationships as $relationship) {
            $hasRelated = $this->model->whereIn('id', $ids)
                ->whereHas($relationship, function ($query) {
                    $query->where('is_delete', IsDelete::FALSE->value);
                })
                ->exists();

            if ($hasRelated) {
                throw new \LogicException(
                    'Cannot delete record(s) with existing '.$relationship
                );
            }
        }
    }
}
