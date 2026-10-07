<?php

declare(strict_types=1);

namespace App\Repositories\Master;

use App\Models\Master\FeatureMst;
use App\Repositories\SoftDeleteCrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FeatureMstRepository extends SoftDeleteCrudRepository
{
    protected array $deleteBlockedBy = ['apis'];

    public function __construct(FeatureMst $model)
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
                'group_name',
                'status',
                'created_at',
                'updated_at',
            ])
            ->with(['apis:id,name,path,type']) // Eager load
            ->notDeleted();

        // Apply filters
        $this->applyFilters($query, $payload, [
            'id',
            'status',
        ], [
            'name',
            'group_name',
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
