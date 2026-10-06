<?php

namespace App\Services\History\Management;

use App\Http\Resources\History\Management\SocialMgmtHistResource;
use App\Repositories\History\Management\SocialMgmtHistRepository;
use Illuminate\Http\Resources\Json\JsonResource;

class SocialMgmtHistService
{
    public function __construct(
        protected SocialMgmtHistRepository $socialMgmtHist
    ) {}

    /**
     * Get social mgmt hist list
     */
    public function list(array $payload): JsonResource
    {
        $list = $this->socialMgmtHist->list($payload);

        return SocialMgmtHistResource::collection($list);
    }

    /**
     * Store social mgmt hist
     */
    public function store(array $payload): int
    {
        return $this->socialMgmtHist->executeStore($payload);
    }

    /**
     * Update social mgmt hist
     */
    public function update(array $payload): int
    {
        return $this->socialMgmtHist->executeUpdate($payload);
    }

    /**
     * Delete social mgmt hist
     */
    public function delete(array $payload): void
    {
        $this->socialMgmtHist->executeDelete($payload['ids']);
    }
}
