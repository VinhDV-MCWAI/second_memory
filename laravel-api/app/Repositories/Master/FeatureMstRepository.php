<?php

declare(strict_types=1);

namespace App\Repositories\Master;

use App\Enums\IsDelete;
use App\Interfaces\Master\FeatureMstInterface;
use App\Models\Master\FeatureMst;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class FeatureMstRepository extends BaseRepository implements FeatureMstInterface
{
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

    /**
     * Create new record
     */
    public function executeStore(array $payload): int
    {
        $model = $this->model->newInstance()->fill(
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

        if ($model->isDeleted()) {
            throw new \LogicException('Cannot update deleted record');
        }

        $model->fill(Arr::only($payload, $this->model->getFillable()));
        $model->save();

        return $model->id;
    }

    /**
     * Delete record (soft delete)
     */
    public function executeDelete(array $ids): void
    {
        // Check if features have dependent APIs
        $this->checkCanDelete($ids, ['apis']);

        // Soft delete
        $this->model->whereIn('id', $ids)
            ->notDeleted()
            ->update(['is_delete' => IsDelete::TRUE->value]);
    }
}
