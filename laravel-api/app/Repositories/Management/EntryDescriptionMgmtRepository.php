<?php

declare(strict_types=1);

namespace App\Repositories\Management;

use App\Models\Management\EntryDescriptionMgmt;
use App\Repositories\SoftDeleteCrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EntryDescriptionMgmtRepository extends SoftDeleteCrudRepository
{
    public function __construct(EntryDescriptionMgmt $model)
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
                'title',
                'summary',
                'article',
                'status',
                'is_display',
                'rank_order',
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
            'title',
            'summary',
            'article',
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
     * Search descriptions
     */
    public function searchDescriptions(string $query): Collection
    {
        return $this->model->query()
            ->select([
                'id',
                'title',
                'summary',
                'article',
                'rank_order',
            ])
            ->where('is_display', true)
            ->where('status', 1)
            ->notDeleted()
            ->where(function ($q) use ($query) {
                $q->where('title', 'ILIKE', "%{$query}%")
                    ->orWhere('summary', 'ILIKE', "%{$query}%")
                    ->orWhere('article', 'ILIKE', "%{$query}%");
            })
            ->orderBy('rank_order', 'asc')
            ->limit(10)
            ->get();
    }
}
