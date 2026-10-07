<?php

declare(strict_types=1);

namespace App\Repositories\Management;

use App\Models\Management\SettingLinkMgmt;
use App\Repositories\SoftDeleteCrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SettingLinkMgmtRepository extends SoftDeleteCrudRepository
{
    public function __construct(SettingLinkMgmt $model)
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
                'key',
                'value',
                'updated_at',
            ])
            ->notDeleted();

        // Apply filters
        $this->applyFilters($query, $payload, [
            'id',
        ], [
            'key',
            'value',
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
