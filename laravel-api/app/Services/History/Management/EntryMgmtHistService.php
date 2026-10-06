<?php

namespace App\Services\History\Management;

use App\Http\Resources\History\Management\EntryMgmtHistResource;
use App\Repositories\History\Management\EntryMgmtHistRepository;
use Illuminate\Http\Resources\Json\JsonResource;

class EntryMgmtHistService
{
    public function __construct(
        protected EntryMgmtHistRepository $entryMgmtHist
    ) {}

    /**
     * Get entry mgmt hist list
     */
    public function list(array $payload): JsonResource
    {
        $list = $this->entryMgmtHist->list($payload);

        return EntryMgmtHistResource::collection($list);
    }

    /**
     * Store entry mgmt hist
     */
    public function store(array $payload): int
    {
        return $this->entryMgmtHist->executeStore($payload);
    }

    /**
     * Update entry mgmt hist
     */
    public function update(array $payload): int
    {
        return $this->entryMgmtHist->executeUpdate($payload);
    }

    /**
     * Delete entry mgmt hist
     */
    public function delete(array $payload): void
    {
        $this->entryMgmtHist->executeDelete($payload['ids']);
    }
}
