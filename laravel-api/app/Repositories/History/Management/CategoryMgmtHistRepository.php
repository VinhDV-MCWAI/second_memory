<?php

declare(strict_types=1);

namespace App\Repositories\History\Management;

use App\Models\History\Management\CategoryMgmtHist;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class CategoryMgmtHistRepository extends BaseRepository
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

    /**
     * Create new record
     */
    public function executeStore(array $payload): int
    {
        $model = $this->model->newInstance();
        $model->fill(
            Arr::only($payload, $this->model->getFillable())
        );
        $model->save();

        return $model->id;
    }

    /**
     * Update record
     */
    public function executeUpdate(array $payload): int
    {
        $model = $this->model->findOrFail($payload['id']);
        $model->fill(Arr::only($payload, $this->model->getFillable()));
        $model->save();

        return $model->id;
    }

    /**
     * Delete record
     */
    public function executeDelete(array $ids): void
    {
        $this->model->whereIn('id', $ids)->delete();
    }
}
