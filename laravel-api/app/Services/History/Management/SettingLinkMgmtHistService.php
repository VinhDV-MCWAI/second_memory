<?php

namespace App\Services\History\Management;

use App\Http\Resources\History\Management\SettingLinkMgmtHistResource;
use App\Repositories\History\Management\SettingLinkMgmtHistRepository;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingLinkMgmtHistService
{
    public function __construct(
        protected SettingLinkMgmtHistRepository $settingLinkMgmtHist
    ) {}

    /**
     * Get setting link mgmt hist list
     */
    public function list(array $payload): JsonResource
    {
        $list = $this->settingLinkMgmtHist->list($payload);

        return SettingLinkMgmtHistResource::collection($list);
    }

    /**
     * Store setting link mgmt hist
     */
    public function store(array $payload): int
    {
        return $this->settingLinkMgmtHist->executeStore($payload);
    }

    /**
     * Update setting link mgmt hist
     */
    public function update(array $payload): int
    {
        return $this->settingLinkMgmtHist->executeUpdate($payload);
    }

    /**
     * Delete setting link mgmt hist
     */
    public function delete(array $payload): void
    {
        $this->settingLinkMgmtHist->executeDelete($payload['ids']);
    }
}
