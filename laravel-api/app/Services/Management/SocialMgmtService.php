<?php

namespace App\Services\Management;

use App\Enums\ActionType;
use App\Http\Resources\Management\SocialMgmtResource;
use App\Interfaces\History\Management\SocialMgmtHistInterface;
use App\Interfaces\Management\SocialMgmtInterface;
use App\Services\BaseService;
use Illuminate\Http\Resources\Json\JsonResource;

class SocialMgmtService extends BaseService
{
    public function __construct(
        protected SocialMgmtInterface $socialMgmt,
        protected SocialMgmtHistInterface $socialMgmtHist
    ) {}

    protected function getHistoryRepository()
    {
        return $this->socialMgmtHist;
    }

    protected function getHistoryForeignKey(): string
    {
        return 'social_mgmt_id';
    }

    /**
     * Get social mgmt list
     */
    public function list(array $payload): JsonResource
    {
        $list = $this->socialMgmt->list($payload);

        return SocialMgmtResource::collection($list);
    }

    /**
     * Store social mgmt
     */
    public function store(array $payload): int
    {
        $id = $this->socialMgmt->executeStore($payload);
        $this->recordHistory($id, ActionType::CREATE, $payload);

        return $id;
    }

    /**
     * Update social mgmt
     */
    public function update(array $payload): int
    {
        $id = $payload['id'];
        $affected = $this->socialMgmt->executeUpdate($payload);
        $this->recordHistory($id, ActionType::UPDATE, $payload);

        return $affected;
    }

    /**
     * Delete social mgmt
     */
    public function delete(array $payload): void
    {
        if (! isset($payload['ids']) || ! is_array($payload['ids'])) {
            $this->socialMgmt->executeDelete($payload['ids'] ?? []);

            return;
        }

        foreach ($payload['ids'] as $id) {
            $this->recordHistory($id, ActionType::DELETE, $payload);
        }

        $this->socialMgmt->executeDelete($payload['ids']);
    }
}
