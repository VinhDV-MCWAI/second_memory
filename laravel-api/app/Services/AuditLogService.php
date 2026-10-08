<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Resources\Audit\AuditLogResource;
use App\Repositories\Audit\AuditLogRepository;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Read side of the audit log (ADR-0006); rows are written by AuditLogger only.
 */
final class AuditLogService
{
    public function __construct(private readonly AuditLogRepository $auditLogs) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function list(array $payload): AnonymousResourceCollection
    {
        return AuditLogResource::collection($this->auditLogs->list($payload));
    }
}
