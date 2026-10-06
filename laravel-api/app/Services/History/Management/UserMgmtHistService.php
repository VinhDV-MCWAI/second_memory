<?php

namespace App\Services\History\Management;

use App\Http\Resources\History\Management\UserMgmtHistResource;
use App\Interfaces\History\Management\UserMgmtHistInterface;
use Illuminate\Http\Resources\Json\JsonResource;

class UserMgmtHistService
{
    public function __construct(
        protected UserMgmtHistInterface $userMgmtHist
    ) {}

    /**
     * Get user mgmt hist list
     */
    public function list(array $payload): JsonResource
    {
        $list = $this->userMgmtHist->list($payload);

        return UserMgmtHistResource::collection($list);
    }

    /**
     * Store user mgmt hist
     */
    public function store(array $payload): int
    {
        return $this->userMgmtHist->executeStore($payload);
    }

    /**
     * Update user mgmt hist
     */
    public function update(array $payload): int
    {
        return $this->userMgmtHist->executeUpdate($payload);
    }

    /**
     * Delete user mgmt hist
     */
    public function delete(array $payload): void
    {
        $this->userMgmtHist->executeDelete($payload['ids']);
    }
}
