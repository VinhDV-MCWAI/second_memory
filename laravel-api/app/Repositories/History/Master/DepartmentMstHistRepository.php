<?php

declare(strict_types=1);

namespace App\Repositories\History\Master;

use App\Models\History\Master\DepartmentMstHist;
use App\Repositories\CrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DepartmentMstHistRepository extends CrudRepository
{
    public function __construct(DepartmentMstHist $model)
    {
        parent::__construct($model);
    }

    /**
     * Get list with pagination
     */
    public function list(array $payload): LengthAwarePaginator
    {
        $query = $this->model->query()
            ->select(['id', 'department_mst_id', 'code', 'name', 'status', 'action', 'author_id'])
            ->with(['departmentMst:id,code,name', 'author:id,user_name']);
        $this->applyFilters($query, $payload, ['department_mst_id', 'status', 'action', 'author_id'], ['code', 'name']);
        $this->applyDateRange($query, $payload);
        $this->applySorting($query, $payload);

        return $query->paginate($payload['per_page'] ?? 15, ['*'], 'page', $payload['page'] ?? 1);
    }
}
