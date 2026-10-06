<?php

namespace App\Services\Management;

use App\Enums\ActionType;
use App\Http\Resources\Management\EntryMgmtResource;
use App\Interfaces\History\Management\EntryMgmtHistInterface;
use App\Interfaces\Management\EntryMgmtInterface;
use App\Services\BaseService;
use Illuminate\Http\Resources\Json\JsonResource;

class EntryMgmtService extends BaseService
{
    public function __construct(
        protected EntryMgmtInterface $entryMgmt,
        protected EntryMgmtHistInterface $entryMgmtHist
    ) {}

    protected function getHistoryRepository()
    {
        return $this->entryMgmtHist;
    }

    protected function getHistoryForeignKey(): string
    {
        return 'entry_mgmt_id';
    }

    /**
     * Get entry mgmt list
     */
    public function list(array $payload): JsonResource
    {
        $list = $this->entryMgmt->list($payload);

        return EntryMgmtResource::collection($list);
    }

    /**
     * Store entry mgmt
     */
    public function store(array $payload): int
    {
        $id = $this->entryMgmt->executeStore($payload);
        $this->recordHistory($id, ActionType::CREATE, $payload);

        return $id;
    }

    /**
     * Update entry mgmt
     */
    public function update(array $payload): int
    {
        $id = $payload['id'];
        $affected = $this->entryMgmt->executeUpdate($payload);
        $this->recordHistory($id, ActionType::UPDATE, $payload);

        return $affected;
    }

    /**
     * Delete entry mgmt
     */
    public function delete(array $payload): void
    {
        if (! isset($payload['ids']) || ! is_array($payload['ids'])) {
            $this->entryMgmt->executeDelete($payload['ids'] ?? []);

            return;
        }

        foreach ($payload['ids'] as $id) {
            $this->recordHistory($id, ActionType::DELETE, $payload);
        }

        $this->entryMgmt->executeDelete($payload['ids']);
    }
}
