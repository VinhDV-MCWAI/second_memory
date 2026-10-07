<?php

declare(strict_types=1);

namespace App\Repositories\History\Master;

use App\Models\History\Master\FeatureMstHist;
use App\Repositories\CrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FeatureMstHistRepository extends CrudRepository
{
    public function __construct(FeatureMstHist $model)
    {
        parent::__construct($model);
    }

    /**
     * Get list with pagination
     */
    public function list(array $payload): LengthAwarePaginator
    {
        $query = $this->model->query()
            ->select(['id', 'feature_mst_id', 'name', 'group_name', 'description', 'status', 'action', 'author_id'])
            ->with(['featureMst:id,name,group_name', 'author:id,user_name']);
        $this->applyFilters($query, $payload, ['feature_mst_id', 'status', 'action', 'author_id'], ['name', 'group_name', 'description']);
        $this->applyDateRange($query, $payload);
        $this->applySorting($query, $payload);

        return $query->paginate($payload['per_page'] ?? 15, ['*'], 'page', $payload['page'] ?? 1);
    }
}
