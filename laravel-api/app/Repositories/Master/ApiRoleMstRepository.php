<?php

declare(strict_types=1);

namespace App\Repositories\Master;

use App\Interfaces\Master\ApiRoleMstInterface;
use App\Models\Master\ApiRoleMst;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ApiRoleMstRepository extends BaseRepository implements ApiRoleMstInterface
{
    public function __construct(ApiRoleMst $model)
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
                'api_role_mst.api_mst_id',
                'api_role_mst.role_mst_id',
                'api_role_mst.updated_at',
            ])
            ->with(['apiMst:id,name,path', 'roleMst:id,name']); // Eager load

        // Apply filters
        $this->applyFilters($query, $payload, [
            'api_mst_id',
            'role_mst_id',
        ]);

        // Apply date range
        $this->applyDateRange($query, $payload);

        // Apply sorting
        $this->applySorting($query, $payload, 'api_mst_id');

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
        $records = [];

        foreach ($payload as $record) {
            $records[] = array_merge($record, [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (! empty($records)) {
            $this->model->insert($records);
        }
    }

    /**
     * Delete record
     */
    public function executeDelete(array $payload): void
    {
        $values = collect($payload)->map(function ($item) {
            return '('.(int) $item['api_mst_id'].', '.(int) $item['role_mst_id'].')';
        })->all();

        $this->model
            ->whereRaw('(api_mst_id, role_mst_id) IN ('.implode(', ', $values).')')
            ->delete();
    }

    /**
     * Get ids
     */
    public function getApiRoleMstId(array $tuples): Collection
    {
        $values = collect($tuples)->map(function ($item) {
            $apiMstId = $item['api_mst_id'] ?? $item[0] ?? null;
            $roleMstId = $item['role_mst_id'] ?? $item[1] ?? null;

            return '('.(int) $apiMstId.', '.(int) $roleMstId.')';
        })->all();

        return $this->model
            ->whereRaw('(api_mst_id, role_mst_id) IN ('.implode(', ', $values).')')
            ->get(['api_mst_id', 'role_mst_id'])
            ->map(function ($item) {
                return [$item->api_mst_id, $item->role_mst_id];
            });
    }

    /**
     * Check if the role belongs to current user
     */
    public function isMyRole(array $payload): bool
    {
        $currentAdminId = (int) (request()->attributes->get('current_admin_id') ?? \Illuminate\Support\Facades\Auth::id());

        if (! $currentAdminId) {
            return false;
        }

        $roleIds = [];
        $checkGroups = ['insert', 'delete'];

        foreach ($checkGroups as $group) {
            if (isset($payload[$group]) && is_array($payload[$group])) {
                foreach ($payload[$group] as $item) {
                    if (isset($item['role_mst_id'])) {
                        $roleIds[] = (int) $item['role_mst_id'];
                    }
                }
            }
        }

        if (empty($roleIds)) {
            return false;
        }

        return \Illuminate\Support\Facades\DB::table('admin_role_mst')
            ->where('admin_mst_id', $currentAdminId)
            ->whereIn('role_mst_id', array_unique($roleIds))
            ->exists();
    }
}
