<?php

declare(strict_types=1);

namespace App\Repositories\Master;

use App\Interfaces\Master\AdminRoleMstInterface;
use App\Models\Master\AdminRoleMst;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AdminRoleMstRepository extends BaseRepository implements AdminRoleMstInterface
{
    public function __construct(AdminRoleMst $model)
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
                'role_mst_id',
                'updated_at',
            ])
            ->with(['adminMst:id,user_name,email', 'roleMst:id,name']); // Eager load

        // Apply filters
        $this->applyFilters($query, $payload, [
            'admin_mst_id',
            'role_mst_id',
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
        foreach ($payload as $record) {
            $this->model->create($record);
        }
    }

    /**
     * Delete record
     */
    public function executeDelete(array $payload): void
    {
        $values = collect($payload)->map(function ($item) {
            return '('.(int) $item['admin_mst_id'].', '.(int) $item['role_mst_id'].')';
        })->all();

        $this->model
            ->whereRaw('(admin_mst_id, role_mst_id) IN ('.implode(', ', $values).')')
            ->delete();
    }

    /**
     * Get ids
     */
    public function getAdminRoleMstId(array $tuples): Collection
    {
        $values = collect($tuples)->map(function ($item) {
            $adminMstId = $item['admin_mst_id'] ?? $item[0] ?? null;
            $roleMstId = $item['role_mst_id'] ?? $item[1] ?? null;

            return '('.(int) $adminMstId.', '.(int) $roleMstId.')';
        })->all();

        return $this->model
            ->whereRaw('(admin_mst_id, role_mst_id) IN ('.implode(', ', $values).')')
            ->get(['admin_mst_id', 'role_mst_id'])
            ->map(function ($item) {
                return [$item->admin_mst_id, $item->role_mst_id];
            });
    }

    /**
     * Check if payload contains current user's role
     */
    public function isMyRole(array $payload): bool
    {
        $currentAdminId = (int) (request()->attributes->get('current_admin_id') ?? \Illuminate\Support\Facades\Auth::id());

        if (! $currentAdminId) {
            return false;
        }

        $checkGroups = ['insert', 'delete'];

        foreach ($checkGroups as $group) {
            if (isset($payload[$group]) && is_array($payload[$group])) {
                foreach ($payload[$group] as $item) {
                    if (isset($item['admin_mst_id']) && (int) $item['admin_mst_id'] === $currentAdminId) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
