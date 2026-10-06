<?php

declare(strict_types=1);

namespace App\Repositories\Master;

use App\Constants\CommonVal;
use App\Models\Master\TokenMst;
use App\Repositories\BaseRepository;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

class TokenMstRepository extends BaseRepository
{
    public function __construct(TokenMst $model)
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
                'token_hash',
                'account_id',
                'device_name',
                'ip_address',
                'expired_at',
                'created_at',
                'updated_at',
            ]);

        // Apply filters
        $this->applyFilters($query, $payload, [
            'id',
            'account_id',
        ], [
            'token_hash',
            'device_name',
            'ip_address',
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
        if (isset($payload['expired_at'])) {
            $payload['expired_at'] = Carbon::createFromFormat(CommonVal::DATE_FORMAT, $payload['expired_at'])->format('Y-m-d');
        }

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

        if (isset($payload['expired_at'])) {
            $payload['expired_at'] = Carbon::createFromFormat(CommonVal::DATE_FORMAT, $payload['expired_at'])->format('Y-m-d');
        }

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
