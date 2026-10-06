<?php

namespace App\Services\Management;

use App\Enums\ActionType;
use App\Http\Resources\Management\SettingLinkMgmtResource;
use App\Interfaces\History\Management\SettingLinkMgmtHistInterface;
use App\Interfaces\Management\SettingLinkMgmtInterface;
use App\Services\BaseService;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingLinkMgmtService extends BaseService
{
    public function __construct(
        protected SettingLinkMgmtInterface $settingLinkMgmt,
        protected SettingLinkMgmtHistInterface $settingLinkMgmtHist
    ) {}

    protected function getHistoryRepository()
    {
        return $this->settingLinkMgmtHist;
    }

    protected function getHistoryForeignKey(): string
    {
        return 'setting_link_mgmt_id';
    }

    /**
     * Get setting link mgmt list
     */
    public function list(array $payload): JsonResource
    {
        $list = $this->settingLinkMgmt->list($payload);

        return SettingLinkMgmtResource::collection($list);
    }

    /**
     * Store setting link mgmt
     */
    public function store(array $payload): int
    {
        $id = $this->settingLinkMgmt->executeStore($payload);
        $this->recordHistory($id, ActionType::CREATE, $payload);

        return $id;
    }

    /**
     * Update setting link mgmt
     */
    public function update(array $payload): int
    {
        $id = $payload['id'];
        $affected = $this->settingLinkMgmt->executeUpdate($payload);
        $this->recordHistory($id, ActionType::UPDATE, $payload);

        return $affected;
    }

    /**
     * Delete setting link mgmt
     */
    public function delete(array $payload): void
    {
        if (! isset($payload['ids']) || ! is_array($payload['ids'])) {
            $this->settingLinkMgmt->executeDelete($payload['ids'] ?? []);

            return;
        }

        foreach ($payload['ids'] as $id) {
            $this->recordHistory($id, ActionType::DELETE, $payload);
        }

        $this->settingLinkMgmt->executeDelete($payload['ids']);
    }
}
