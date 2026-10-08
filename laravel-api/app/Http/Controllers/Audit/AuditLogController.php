<?php

declare(strict_types=1);

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use App\Http\Requests\Audit\ListAuditLogRequest;
use App\Http\Resources\Audit\AuditLogResource;
use App\Services\AuditLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class AuditLogController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogs) {}

    /**
     * Audit log, newest first by default (read-only, ADR-0006).
     *
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, AuditLogResource>>
     */
    public function list(ListAuditLogRequest $request): AnonymousResourceCollection
    {
        return $this->auditLogs->list($request->validated());
    }
}
