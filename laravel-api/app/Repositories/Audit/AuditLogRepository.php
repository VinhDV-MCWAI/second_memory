<?php

declare(strict_types=1);

namespace App\Repositories\Audit;

use App\Models\Audit\AuditLog;
use Illuminate\Database\Eloquent\Builder;

final class AuditLogRepository
{
    public function __construct(private readonly AuditLog $model) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): AuditLog
    {
        return $this->model->newQuery()->create($attributes);
    }

    /**
     * @return Builder<AuditLog>
     */
    public function query(): Builder
    {
        return $this->model->newQuery();
    }
}
