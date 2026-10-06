<?php

declare(strict_types=1);

namespace App\Repositories\Management;

use App\Enums\IsDelete;
use App\Interfaces\Management\SocialMgmtInterface;
use App\Models\Management\SocialMgmt;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class SocialMgmtRepository extends BaseRepository implements SocialMgmtInterface
{
    public function __construct(SocialMgmt $model)
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
                'slug',
                'link',
                'image',
                'status',
                'is_display',
                'rank_order',
                'updated_at',
            ])
            ->notDeleted();

        // Apply filters
        $this->applyFilters($query, $payload, [
            'id',
            'link',
            'image',
            'status',
            'is_display',
            'rank_order',
        ], [
            'name',
            'slug',
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
        // Soft delete
        $this->model->whereIn('id', $ids)
            ->notDeleted()
            ->update(['is_delete' => IsDelete::TRUE->value]);
    }
}
