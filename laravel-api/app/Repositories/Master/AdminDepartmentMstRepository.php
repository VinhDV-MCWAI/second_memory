<?php

declare(strict_types=1);

namespace App\Repositories\Master;

use App\Interfaces\Master\AdminDepartmentMstInterface;
use App\Models\Master\AdminDepartmentMst;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AdminDepartmentMstRepository extends BaseRepository implements AdminDepartmentMstInterface
{
    public function __construct(AdminDepartmentMst $model)
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
                'admin_mst_id',
                'department_mst_id',
                // removed updated_at
            ])
            ->with(['adminMst:id,user_name,email', 'departmentMst:id,code,name']); // Eager load

        // Apply filters
        $this->applyFilters($query, $payload, [
            'admin_mst_id',
            'department_mst_id',
        ]);

        // Apply date range
        $this->applyDateRange($query, $payload);

        // Apply sorting
        $this->applySorting($query, $payload, 'admin_mst_id');

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
        // Use insert instead of create to handle array of arrays and bypass timestamp issue
        $this->model->insert($payload);
    }

    /**
     * Delete record
     */
    public function executeDelete(array $payload): void
    {
        $values = collect($payload)->map(function ($item) {
            $adminId = $item['admin_mst_id'] ?? ($item[0] ?? 0);
            $deptId = $item['department_mst_id'] ?? ($item[1] ?? 0);

            return '('.(int) $adminId.', '.(int) $deptId.')';
        })->all();

        $this->model
            ->whereRaw('(admin_mst_id, department_mst_id) IN ('.implode(', ', $values).')')
            ->delete();
    }

    /**
     * Get ids
     */
    public function getAdminDepartmentMstId(array $tuples): Collection
    {
        // Handle both Associative and Indexed Array (from BaseJunctionService)
        $values = collect($tuples)->map(function ($item) {
            $adminId = $item['admin_mst_id'] ?? ($item[0] ?? 0);
            $deptId = $item['department_mst_id'] ?? ($item[1] ?? 0);

            return '('.(int) $adminId.', '.(int) $deptId.')';
        })->all();

        return $this->model
            ->whereRaw('(admin_mst_id, department_mst_id) IN ('.implode(', ', $values).')')
            ->pluck('admin_mst_id', 'department_mst_id');
    }
}
