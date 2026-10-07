<?php

declare(strict_types=1);

namespace App\Repositories\History\Management;

use App\Models\History\Management\CategoryMgmtHist;
use App\Repositories\CrudRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CategoryMgmtHistRepository extends CrudRepository
{
    public function __construct(CategoryMgmtHist $model)
    {
        parent::__construct($model);
    }

    /**
     * Get list
     */
    public function list(array $payload): LengthAwarePaginator
    {
        $query = $this->model->query()
            ->select([
                'id',
                'category_mgmt_id',
                'name',
                'slug',
                'description',
                'status',
                'is_display',
                'rank_order',
                'action',
                'author_id',
            ])
            ->with(['categoryMgmt:id,name', 'author:id,user_name']);

        $this->applyFilters($query, $payload, [
            'category_mgmt_id',
            'status',
            'is_display',
            'rank_order',
            'action',
            'author_id',
        ], [
            'name',
            'slug',
            'description',
        ]);

        $this->applyDateRange($query, $payload);
        $this->applySorting($query, $payload);

        $perPage = $payload['per_page'] ?? 15;
        $page = $payload['page'] ?? 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
