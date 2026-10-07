<?php

declare(strict_types=1);

namespace App\Repositories\Master;

use App\Constants\CommonVal;
use App\Models\Master\TokenMst;
use App\Repositories\CrudRepository;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TokenMstRepository extends CrudRepository
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

    protected function fillable(array $payload): array
    {
        if (isset($payload['expired_at'])) {
            $payload['expired_at'] = Carbon::createFromFormat(CommonVal::DATE_FORMAT, $payload['expired_at'])->format('Y-m-d');
        }

        return parent::fillable($payload);
    }
}
