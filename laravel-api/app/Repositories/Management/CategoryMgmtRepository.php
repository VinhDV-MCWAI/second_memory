<?php

declare(strict_types=1);

namespace App\Repositories\Management;

use App\Models\Management\CategoryMgmt;
use App\Repositories\SoftDeleteCrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CategoryMgmtRepository extends SoftDeleteCrudRepository
{
    public function __construct(CategoryMgmt $model)
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
                'description',
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
            'description',
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
     * Get displayable categories for docs
     */
    public function getDisplayableCategories(): Collection
    {
        return $this->model->query()
            ->select([
                'id',
                'name',
                'slug',
                'description',
                'rank_order',
                'layout_structure',
            ])
            ->where('is_display', true)
            ->where('status', 1)
            ->notDeleted()
            ->orderBy('rank_order', 'asc')
            ->get();
    }

    /**
     * Search categories
     */
    public function searchCategories(string $query): Collection
    {
        return $this->model->query()
            ->select([
                'id',
                'name',
                'slug',
                'description',
                'rank_order',
            ])
            ->where('is_display', true)
            ->where('status', 1)
            ->notDeleted()
            ->where(function ($q) use ($query) {
                $q->where('name', 'ILIKE', "%{$query}%")
                    ->orWhere('description', 'ILIKE', "%{$query}%");
            })
            ->orderBy('rank_order', 'asc')
            ->limit(10)
            ->get();
    }
}
