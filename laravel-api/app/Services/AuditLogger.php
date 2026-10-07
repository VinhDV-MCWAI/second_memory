<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditEvent;
use App\Repositories\Audit\AuditLogRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Writes audit_log rows (ADR-0006). Secrets never reach the log.
 */
final class AuditLogger
{
    /** Keys that are never written to the log, and keys that change on every save. */
    private const EXCLUDED_KEYS = ['id', 'password', 'remember_token', 'updated_at'];

    public function __construct(private readonly AuditLogRepository $auditLogs) {}

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    public function record(
        string $auditableType,
        ?int $auditableId,
        AuditEvent $event,
        ?array $old = null,
        ?array $new = null,
        ?int $legacyHistId = null,
    ): void {
        $old = $old === null ? null : Arr::except($old, self::EXCLUDED_KEYS);
        $new = $new === null ? null : Arr::except($new, self::EXCLUDED_KEYS);

        if ($event === AuditEvent::UPDATED && $old !== null && $new !== null) {
            [$old, $new] = $this->changesOnly($old, $new);
        }

        $this->auditLogs->create([
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'event' => $event,
            'old_values' => $old,
            'new_values' => $new,
            'admin_mst_id' => Auth::id(),
            'ip_address' => Request::ip(),
            'legacy_hist_id' => $legacyHistId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function changesOnly(array $old, array $new): array
    {
        $changed = array_keys(array_filter(
            $new,
            fn (mixed $value, string $key): bool => ! array_key_exists($key, $old) || $old[$key] !== $value,
            ARRAY_FILTER_USE_BOTH,
        ));

        return [Arr::only($old, $changed), Arr::only($new, $changed)];
    }
}
