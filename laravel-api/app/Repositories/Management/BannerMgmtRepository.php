<?php

declare(strict_types=1);

namespace App\Repositories\Management;

use App\Enums\IsDelete;
use App\Interfaces\Management\BannerMgmtInterface;
use App\Models\Management\BannerMgmt;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class BannerMgmtRepository extends BaseRepository implements BannerMgmtInterface
{
    public function __construct(BannerMgmt $model)
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
                'banner_mgmt.id',
                'banner_mgmt.title',
                'media_mgmt.url as image', // Alias as image to match Resource expectation
                // 'link' Removed
                'banner_mgmt.status',
                'banner_mgmt.updated_at',
                'banner_mgmt.position',
                'banner_mgmt.media_id',
                'banner_mgmt.slug',
                'banner_mgmt.description',
                'banner_mgmt.is_delete',
            ])
            ->leftJoin('media_mgmt', 'banner_mgmt.media_id', '=', 'media_mgmt.id')
            ->where('banner_mgmt.is_delete', IsDelete::FALSE->value);

        // Apply filters
        $this->applyFilters($query, $payload, [
            'id' => 'banner_mgmt.id',
            'status' => 'banner_mgmt.status',
        ], [
            'title' => 'banner_mgmt.title',
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
        // Soft delete
        $this->model->whereIn('id', $ids)
            ->notDeleted()
            ->update(['is_delete' => IsDelete::TRUE->value]);
    }
}
