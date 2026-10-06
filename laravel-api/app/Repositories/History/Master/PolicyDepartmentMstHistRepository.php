<?php

declare(strict_types=1);

namespace App\Repositories\History\Master;

use App\Interfaces\History\Master\PolicyDepartmentMstHistInterface;
use App\Models\History\Master\PolicyDepartmentMstHist;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class PolicyDepartmentMstHistRepository extends BaseRepository implements PolicyDepartmentMstHistInterface
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

    /**
     * Create new record
     */
    public function executeStore(array $payload): int
    {
        $model = $this->model->newInstance()->fill(Arr::only($payload, $this->model->getFillable()));
        $model->save();

        return $model->id;
    }

    /**
     * Update record
     */
    public function executeUpdate(array $payload): int
    {
        $model = $this->model->findOrFail($payload['id']);
        $model->fill(Arr::only($payload, $this->model->getFillable()));
        $model->save();

        return $model->id;
    }

    /**
     * Delete record
     */
    public function executeDelete(array $ids): void
    {
        $this->model->whereIn('id', $ids)->delete();
    }
}
