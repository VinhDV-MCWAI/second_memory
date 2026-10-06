<?php

declare(strict_types=1);

namespace App\Repositories\History\Master;

use App\Models\History\Master\RoleMstHist;
use App\Repositories\CrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RoleMstHistRepository extends CrudRepository
{
    public function __construct(RoleMstHist $model)
    {
        parent::__construct($model);
    }

    /**
     * Get list with pagination
     */
    public function list(array $payload): LengthAwarePaginator
    {
        $query = $this->model->query()
            ->select(['id', 'role_mst_id', 'name', 'permission', 'is_active', 'action', 'author_id'])
            ->with(['roleMst:id,name', 'author:id,user_name']);
        $this->applyFilters($query, $payload, ['role_mst_id', 'is_active', 'action', 'author_id'], ['name', 'permission']);
        $this->applyDateRange($query, $payload);
        $this->applySorting($query, $payload);

        return $query->paginate($payload['per_page'] ?? 15, ['*'], 'page', $payload['page'] ?? 1);
    }
}
