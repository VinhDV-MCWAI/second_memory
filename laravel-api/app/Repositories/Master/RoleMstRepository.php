<?php

declare(strict_types=1);

namespace App\Repositories\Master;

use App\Models\Master\RoleMst;
use App\Repositories\SoftDeleteCrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RoleMstRepository extends SoftDeleteCrudRepository
{
    protected array $deleteBlockedBy = ['admins', 'apis'];

    public function __construct(RoleMst $model)
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
                'name',
                'permission',
                'is_active',
                'updated_at',
            ])
            ->with(['admins:id,user_name,email', 'apis:id,name,path']) // Eager load relationships
            ->notDeleted(); // Use scope

        // Apply filters
        $this->applyFilters($query, $payload, [
            'id',
            'is_active',
        ], [
            'name',
            'permission',
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
