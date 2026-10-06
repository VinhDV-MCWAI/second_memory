<?php

namespace App\Services\Management;

use App\Enums\ActionType;
use App\Http\Resources\Management\EntryDescriptionMgmtResource;
use App\Repositories\History\Management\EntryDescriptionMgmtHistRepository;
use App\Repositories\Management\EntryDescriptionMgmtRepository;
use App\Services\BaseService;
use Illuminate\Http\Resources\Json\JsonResource;

class EntryDescriptionMgmtService extends BaseService
{
    public function __construct(
        protected EntryDescriptionMgmtRepository $entryDescriptionMgmt,
        protected EntryDescriptionMgmtHistRepository $entryDescriptionMgmtHist
    ) {}

    protected function getHistoryRepository()
    {
        return $this->entryDescriptionMgmtHist;
    }

    protected function getHistoryForeignKey(): string
    {
        return 'entry_description_mgmt_id';
    }

    /**
     * Get entry description mgmt list
     */
    public function list(array $payload): JsonResource
    {
        $list = $this->entryDescriptionMgmt->list($payload);

        return EntryDescriptionMgmtResource::collection($list);
    }

    /**
     * Store entry description mgmt
     */
    public function store(array $payload): int
    {
        $id = $this->entryDescriptionMgmt->executeStore($payload);
        $this->recordHistory($id, ActionType::CREATE, $payload);

        return $id;
    }

    /**
     * Update entry description mgmt
     */
    public function update(array $payload): int
    {
        $id = $payload['id'];
        $affected = $this->entryDescriptionMgmt->executeUpdate($payload);
        $this->recordHistory($id, ActionType::UPDATE, $payload);

        return $affected;
    }

    /**
     * Delete entry description mgmt
     */
    public function delete(array $payload): void
    {
        if (! isset($payload['ids']) || ! is_array($payload['ids'])) {
            $this->entryDescriptionMgmt->executeDelete($payload['ids'] ?? []);

            return;
        }

        foreach ($payload['ids'] as $id) {
            $this->recordHistory($id, ActionType::DELETE, $payload);
        }

        $this->entryDescriptionMgmt->executeDelete($payload['ids']);
    }
}
