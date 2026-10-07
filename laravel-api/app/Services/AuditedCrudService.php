<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActionType;
use App\Enums\AuditEvent;
use App\Repositories\CrudRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * CrudService that audits every create, update and delete.
 * RFC-001 slice 9 dual-write: a `*_hist` row (old) and an audit_log row (ADR-0006).
 */
abstract class AuditedCrudService extends CrudService
{
    /** audit_log event -> legacy *_hist action, while both are written. */
    private const LEGACY_ACTIONS = [
        'created' => ActionType::CREATE,
        'updated' => ActionType::UPDATE,
        'deleted' => ActionType::DELETE,
    ];

    /** Column in the history table that points at the audited row, e.g. `admin_mst_id`. */
    protected string $historyForeignKey;

    /** Short alias written to audit_log.auditable_type, e.g. `admin`. */
    protected string $auditableType;

    public function __construct(
        CrudRepository $repository,
        protected CrudRepository $historyRepository,
        protected AuditLogger $auditLogger,
    ) {
        parent::__construct($repository);
    }

    public function store(array $payload): int
    {
        $id = $this->repository->executeStore($payload);
        $this->audit($id, AuditEvent::CREATED, $payload, null);

        return $id;
    }

    public function update(array $payload): int
    {
        $id = (int) $payload['id'];
        $before = $this->snapshot($id);
        $affected = $this->repository->executeUpdate($payload);
        $this->audit($id, AuditEvent::UPDATED, $payload, $before);

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
            $this->audit((int) $id, AuditEvent::DELETED, $payload, $this->snapshot((int) $id));
        }

        $this->repository->executeDelete($payload['ids']);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>|null  $before
     */
    private function audit(int $id, AuditEvent $event, array $payload, ?array $before): void
    {
        $after = $event === AuditEvent::DELETED ? $before : $this->snapshot($id);
        if ($after === null) {
            Log::warning('Record not found for audit', ['service' => static::class, 'id' => $id, 'event' => $event->value]);

            return;
        }

        $legacyHistId = $this->recordHistory($id, self::LEGACY_ACTIONS[$event->value], $payload, $after);

        $this->auditLogger->record(
            $this->auditableType,
            $id,
            $event,
            $event === AuditEvent::CREATED ? null : $before,
            $event === AuditEvent::DELETED ? null : $after,
            $legacyHistId,
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

    /**
     * Legacy history row (removed in the contract step). Returns its id.
     *
     * @param  array<string, mixed>  $snapshot
     */
    private function recordHistory(int $id, ActionType $action, array $payload, array $snapshot): int
    {
        $historyPayload = $snapshot;
        unset(
            $historyPayload['id'],
            $historyPayload['updated_at'],
            $historyPayload['password'],
            $historyPayload['remember_token'],
            $historyPayload['email_verified_at'],
        );

        $historyPayload[$this->historyForeignKey] = $id;
        $historyPayload['action'] = $action;
        $historyPayload['author_id'] = $payload['author_id'] ?? Auth::id();

        return $this->historyRepository->executeStore($historyPayload);
    }
}
