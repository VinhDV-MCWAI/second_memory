<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActionType;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseService
{
    /**
     * Record history after create/update/delete operations
     *
     * @param  int  $id  Record ID
     * @param  ActionType  $action  Action type (CREATE, UPDATE, DELETE)
     * @param  array  $payload  Original payload
     */
    protected function recordHistory(
        int $id,
        ActionType $action,
        array $payload
    ): void {
        try {
            // Get current record data via list method
            $recordResource = $this->list(['id' => $id]);
            $record = $recordResource->collection->first();

            if (! $record) {
                // Log warning when record not found
                \Log::warning('Record not found for history tracking', [
                    'service' => static::class,
                    'id' => $id,
                    'action' => $action->value,
                ]);

                return;
            }

            $historyData = json_decode($record->toJson(), true);
            $historyPayload = $historyData;
            unset($historyPayload['id']);
            unset($historyPayload['updated_at']);
            unset($historyPayload['password']);
            unset($historyPayload['remember_token']);
            unset($historyPayload['email_verified_at']);

            // Set foreign key
            $historyPayload[$this->getHistoryForeignKey()] = $id;
            $historyPayload['action'] = $action;
            $historyPayload['author_id'] = $payload['author_id'] ?? request()->attributes->get('current_admin_id') ?? request()->user()?->id;

            // Call history repository
            $this->getHistoryRepository()->executeStore($historyPayload);
        } catch (\Exception $e) {
            // Log error and re-throw to trigger transaction rollback
            \Log::error('Failed to record history', [
                'service' => static::class,
                'id' => $id,
                'action' => $action->value,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw exception to trigger TransactionMiddleware rollback
            throw $e;
        }
    }

    /**
     * Get history repository instance
     * Must be implemented by child classes
     *
     * @return mixed
     */
    abstract protected function getHistoryRepository();

    /**
     * Get foreign key name for history table
     * Must be implemented by child classes
     */
    abstract protected function getHistoryForeignKey(): string;

    /**
     * List method must be implemented by child classes
     */
    abstract public function list(array $payload): JsonResource|array;
}
