<?php

declare(strict_types=1);

namespace App\Repositories\Master;

use App\Models\Master\PolicyDepartmentMst;
use App\Repositories\SoftDeleteCrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PolicyDepartmentMstRepository extends SoftDeleteCrudRepository
{
    public function __construct(PolicyDepartmentMst $model)
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
                'table_name',
                'row_id',
                'updated_at',
            ])
            ->with(['departments:id,code,name,status,is_delete,updated_at']) // Eager load
            ->notDeleted();

        // Apply filters
        $this->applyFilters($query, $payload, [
            'id',
        ], [
            'table_name',
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
