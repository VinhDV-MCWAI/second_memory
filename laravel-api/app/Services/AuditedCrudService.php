<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActionType;
use App\Repositories\CrudRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * CrudService that writes a history row (`*_hist` table) for every create, update and delete.
 */
abstract class AuditedCrudService extends CrudService
{
    /** Column in the history table that points at the audited row, e.g. `banner_mgmt_id`. */
    protected string $historyForeignKey;

    public function __construct(
        CrudRepository $repository,
        protected CrudRepository $historyRepository,
    ) {
        parent::__construct($repository);
    }

    public function store(array $payload): int
    {
        $id = $this->repository->executeStore($payload);
        $this->recordHistory($id, ActionType::CREATE, $payload);

        return $id;
    }

    public function update(array $payload): int
    {
        $affected = $this->repository->executeUpdate($payload);
        $this->recordHistory((int) $payload['id'], ActionType::UPDATE, $payload);

        return $affected;
    }

    public function delete(array $payload): void
    {
        if (! isset($payload['ids']) || ! is_array($payload['ids'])) {
            $this->repository->executeDelete($payload['ids'] ?? []);

            return;
        }

        // History is written first: it snapshots the row while it is still listed
        foreach ($payload['ids'] as $id) {
            $this->recordHistory((int) $id, ActionType::DELETE, $payload);
        }

        $this->repository->executeDelete($payload['ids']);
    }

    /**
     * Snapshot the row as the list endpoint returns it and store it in the history table.
     * Failures are re-thrown so TransactionMiddleware rolls the whole request back.
     */
    protected function recordHistory(int $id, ActionType $action, array $payload): void
    {
        try {
            $record = $this->list(['id' => $id])->collection->first();

            if (! $record) {
                Log::warning('Record not found for history tracking', [
                    'service' => static::class,
                    'id' => $id,
                    'action' => $action->value,
                ]);

                return;
            }

            $historyPayload = json_decode($record->toJson(), true);
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

            $this->historyRepository->executeStore($historyPayload);
        } catch (\Exception $e) {
            Log::error('Failed to record history', [
                'service' => static::class,
                'id' => $id,
                'action' => $action->value,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
