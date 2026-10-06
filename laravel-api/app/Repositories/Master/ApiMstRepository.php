<?php

declare(strict_types=1);

namespace App\Repositories\Master;

use App\Enums\IsDelete;
use App\Interfaces\Master\ApiMstInterface;
use App\Models\Master\ApiMst;
use App\Models\Master\FeatureMst;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class ApiMstRepository extends BaseRepository implements ApiMstInterface
{
    public function __construct(ApiMst $model)
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
                'type',
                'name',
                'path',
                'is_active',
                'feature_mst_id',
                'updated_at',
            ])
            ->with(['feature:id,name,group_name', 'roles:id,name,permission']) // Eager load
            ->notDeleted();

        // Apply filters
        $this->applyFilters($query, $payload, [
            'id',
            'type',
            'is_active',
            'feature_mst_id',
        ], [
            'name',
            'path',
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
        // Validate foreign keys
        $this->validateForeignKeys([
            'feature_mst_id' => FeatureMst::class,
        ], $payload);

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

        // Validate foreign keys
        $this->validateForeignKeys([
            'feature_mst_id' => FeatureMst::class,
        ], $payload);

        $model->fill(Arr::only($payload, $this->model->getFillable()));
        $model->save();

        return $model->id;
    }

    /**
     * Delete record (soft delete)
     */
    public function executeDelete(array $ids): void
    {
        // Soft delete
        $this->model->whereIn('id', $ids)
            ->notDeleted()
            ->update(['is_delete' => IsDelete::TRUE->value]);
    }
}
