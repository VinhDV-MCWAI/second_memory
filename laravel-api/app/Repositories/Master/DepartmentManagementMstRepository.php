<?php

declare(strict_types=1);

namespace App\Repositories\Master;

use App\Interfaces\Master\DepartmentManagementMstInterface;
use App\Models\Master\DepartmentManagementMst;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class DepartmentManagementMstRepository extends BaseRepository implements DepartmentManagementMstInterface
{
    public function __construct(DepartmentManagementMst $model)
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
                'department_mst_id',
                'policy_department_mst_id',
                'updated_at',
            ])
            ->with(['department:id,code,name', 'policy:id,table_name,row_id']); // Eager load

        // Apply filters
        $this->applyFilters($query, $payload, [
            'department_mst_id',
            'policy_department_mst_id',
        ]);

        // Apply date range
        $this->applyDateRange($query, $payload);

        // Apply sorting
        $this->applySorting($query, $payload, 'department_mst_id');

        // Pagination
        $perPage = $payload['per_page'] ?? 15;
        $page = $payload['page'] ?? 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Create new record
     */
    public function executeStore(array $payload): void
    {
        $now = now();
        $data = collect($payload)->map(function ($item) use ($now) {
            return array_merge($item, [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        })->all();

        $this->model->insert($data);
    }

    /**
     * Delete record
     */
    public function executeDelete(array $payload): void
    {
        $values = collect($payload)->map(function ($item) {
            return '('.(int) $item['department_mst_id'].', '.(int) $item['policy_department_mst_id'].')';
        })->all();

        $this->model
            ->whereRaw('(department_mst_id, policy_department_mst_id) IN ('.implode(', ', $values).')')
            ->delete();
    }

    /**
     * Get ids
     */
    public function getDepartmentManagementMstId(array $tuples): Collection
    {
        $values = collect($tuples)->map(function ($item) {
            $depId = isset($item['department_mst_id']) ? $item['department_mst_id'] : ($item[0] ?? 0);
            $polId = isset($item['policy_department_mst_id']) ? $item['policy_department_mst_id'] : ($item[1] ?? 0);

            return '('.(int) $depId.', '.(int) $polId.')';
        })->all();

        return $this->model
            ->whereRaw('(department_mst_id, policy_department_mst_id) IN ('.implode(', ', $values).')')
            ->get(['department_mst_id', 'policy_department_mst_id'])
            ->map(function ($item) {
                return [$item->department_mst_id, $item->policy_department_mst_id];
            });
    }
}
