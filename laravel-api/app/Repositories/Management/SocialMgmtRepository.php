<?php

declare(strict_types=1);

namespace App\Repositories\Management;

use App\Models\Management\SocialMgmt;
use App\Repositories\SoftDeleteCrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SocialMgmtRepository extends SoftDeleteCrudRepository
{
    public function __construct(SocialMgmt $model)
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
                'link',
                'image',
                'status',
                'is_display',
                'rank_order',
                'updated_at',
            ])
            ->notDeleted();

        // Apply filters
        $this->applyFilters($query, $payload, [
            'id',
            'link',
            'image',
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
}
