<?php

declare(strict_types=1);

namespace App\Repositories\History\Master;

use App\Interfaces\History\Master\RoleMstHistInterface;
use App\Models\History\Master\RoleMstHist;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class RoleMstHistRepository extends BaseRepository implements RoleMstHistInterface
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

    /**
     * Create new record
     */
    public function executeStore(array $payload): int
    {
        $model = $this->model->newInstance()->fill(Arr::only($payload, $this->model->getFillable()));
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
     * Delete record (hard delete)
     */
    public function executeDelete(array $ids): void
    {
        $this->model->whereIn('id', $ids)->delete();
    }
}
