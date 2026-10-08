<?php

declare(strict_types=1);

namespace App\Repositories\Audit;

use App\Models\Audit\AuditLog;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class AuditLogRepository extends BaseRepository
{
    public function __construct(AuditLog $model)
    {
        parent::__construct($model);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): AuditLog
    {
        /** @var AuditLog */
        return $this->model->newQuery()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function list(array $payload): LengthAwarePaginator
    {
        $query = $this->model->query()->with('actor:id,user_name');

        $this->applyFilters($query, $payload, ['id', 'auditable_type', 'auditable_id', 'event', 'admin_mst_id']);
        $this->applyDateRange($query, $payload, 'created_at');
        $this->applySorting($query, $payload, ['id', 'created_at'], 'id', 'desc');

        return $query->paginate($payload['per_page'] ?? 15, ['*'], 'page', $payload['page'] ?? 1);
    }
}
