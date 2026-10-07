<?php

declare(strict_types=1);

namespace App\Repositories\Master;

use App\Models\Master\DepartmentMst;
use App\Repositories\SoftDeleteCrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DepartmentMstRepository extends SoftDeleteCrudRepository
{
    protected array $deleteBlockedBy = ['admins'];

    public function __construct(DepartmentMst $model)
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
                'code',
                'name',
                'status',
                'updated_at',
            ])
            ->with(['admins:id,user_name,email', 'policies:id,table_name,row_id']) // Eager load
            ->notDeleted();

        // Apply filters
        $this->applyFilters($query, $payload, [
            'id',
            'status',
        ], [
            'code',
            'name',
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
