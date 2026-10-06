<?php

namespace App\Services\History\Management;

use App\Http\Resources\History\Management\EntryDescriptionMgmtHistResource;
use App\Interfaces\History\Management\EntryDescriptionMgmtHistInterface;
use Illuminate\Http\Resources\Json\JsonResource;

class EntryDescriptionMgmtHistService
{
    public function __construct(
        protected EntryDescriptionMgmtHistInterface $entryDescriptionMgmtHist
    ) {}

    /**
     * Get entry description mgmt hist list
     */
    public function list(array $payload): JsonResource
    {
        $list = $this->entryDescriptionMgmtHist->list($payload);

        return EntryDescriptionMgmtHistResource::collection($list);
    }

    /**
     * Store entry description mgmt hist
     */
    public function store(array $payload): int
    {
        return $this->entryDescriptionMgmtHist->executeStore($payload);
    }

    /**
     * Update entry description mgmt hist
     */
    public function update(array $payload): int
    {
        return $this->entryDescriptionMgmtHist->executeUpdate($payload);
    }

    /**
     * Delete entry description mgmt hist
     */
    public function delete(array $payload): void
    {
        $this->entryDescriptionMgmtHist->executeDelete($payload['ids']);
    }
}
