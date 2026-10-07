<?php

declare(strict_types=1);

namespace App\Repositories\History\Master;

use App\Models\History\Master\PolicyDepartmentMstHist;
use App\Repositories\CrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PolicyDepartmentMstHistRepository extends CrudRepository
{
    public function __construct(PolicyDepartmentMstHist $model)
    {
        parent::__construct($model);
    }

    /**
     * Get list with pagination
     */
    public function list(array $payload): LengthAwarePaginator
    {
        $query = $this->model->query()
            ->select(['id', 'policy_department_mst_id', 'table_name', 'row_id', 'action', 'author_id'])
            ->with(['policyDepartmentMst:id,table_name,row_id', 'author:id,user_name']);
        $this->applyFilters($query, $payload, ['policy_department_mst_id', 'table_name', 'row_id', 'action', 'author_id'], []);
        $this->applyDateRange($query, $payload);
        $this->applySorting($query, $payload);

        return $query->paginate($payload['per_page'] ?? 15, ['*'], 'page', $payload['page'] ?? 1);
    }
}
