<?php

declare(strict_types=1);

namespace App\Repositories\History\Master;

use App\Models\History\Master\ApiMstHist;
use App\Repositories\CrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ApiMstHistRepository extends CrudRepository
{
    public function __construct(ApiMstHist $model)
    {
        parent::__construct($model);
    }

    /**
     * Get list with pagination
     */
    public function list(array $payload): LengthAwarePaginator
    {
        $query = $this->model->query()
            ->select(['id', 'api_mst_id', 'type', 'name', 'path', 'is_active', 'feature_mst_id', 'action', 'author_id', 'created_at'])
            ->with(['apiMst:id,name,path', 'author:id,user_name']);
        $this->applyFilters($query, $payload, ['api_mst_id', 'type', 'is_active', 'feature_mst_id', 'action', 'author_id'], ['name', 'path']);
        $this->applyDateRange($query, $payload);
        $this->applySorting($query, $payload);

        return $query->paginate($payload['per_page'] ?? 15, ['*'], 'page', $payload['page'] ?? 1);
    }
}
