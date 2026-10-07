<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditEvent;
use App\Repositories\CrudRepository;
use Illuminate\Support\Facades\Log;

/**
 * CrudService that writes an audit_log row for every create, update and delete (ADR-0006).
 */
abstract class AuditedCrudService extends CrudService
{
    /** Short alias written to audit_log.auditable_type, e.g. `admin`. */
    protected string $auditableType;

    public function __construct(
        CrudRepository $repository,
        protected AuditLogger $auditLogger,
    ) {
        parent::__construct($repository);
    }

    public function store(array $payload): int
    {
        $id = $this->repository->executeStore($payload);
        $this->audit($id, AuditEvent::CREATED, null);

        return $id;
    }

    public function update(array $payload): int
    {
        $id = (int) $payload['id'];
        $before = $this->snapshot($id);
        $affected = $this->repository->executeUpdate($payload);
        $this->audit($id, AuditEvent::UPDATED, $before);

        return $affected;
    }

    public function delete(array $payload): void
    {
        if (! isset($payload['ids']) || ! is_array($payload['ids'])) {
            $this->repository->executeDelete($payload['ids'] ?? []);

            return;
        }

        // Audited first: the snapshot is taken while the row is still listed
        foreach ($payload['ids'] as $id) {
            $this->audit((int) $id, AuditEvent::DELETED, $this->snapshot((int) $id));
        }

        $this->repository->executeDelete($payload['ids']);
    }

    /**
     * @param  array<string, mixed>|null  $before
     */
    private function audit(int $id, AuditEvent $event, ?array $before): void
    {
        $after = $event === AuditEvent::DELETED ? $before : $this->snapshot($id);
        if ($after === null) {
            Log::warning('Record not found for audit', ['service' => static::class, 'id' => $id, 'event' => $event->value]);

            return;
        }

        $this->auditLogger->record(
            $this->auditableType,
            $id,
            $event,
            $event === AuditEvent::CREATED ? null : $before,
            $event === AuditEvent::DELETED ? null : $after,
        );
    }

    /**
     * The row as the list endpoint returns it, or null when it is not listed.
     *
     * @return array<string, mixed>|null
     */
    private function snapshot(int $id): ?array
    {
        $record = $this->list(['id' => $id])->collection->first();

        return $record ? json_decode($record->toJson(), true) : null;
    }
}
