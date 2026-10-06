<?php

namespace App\Services\Management;

use App\Enums\ActionType;
use App\Http\Resources\Management\UserMgmtResource;
use App\Repositories\History\Management\UserMgmtHistRepository;
use App\Repositories\Management\UserMgmtRepository;
use App\Services\BaseService;
use Illuminate\Http\Resources\Json\JsonResource;

class UserMgmtService extends BaseService
{
    public function __construct(
        protected UserMgmtRepository $userMgmt,
        protected UserMgmtHistRepository $userMgmtHist
    ) {}

    protected function getHistoryRepository()
    {
        return $this->userMgmtHist;
    }

    protected function getHistoryForeignKey(): string
    {
        return 'user_mgmt_id';
    }

    /**
     * Get user mgmt list
     */
    public function list(array $payload): JsonResource
    {
        $list = $this->userMgmt->list($payload);

        return UserMgmtResource::collection($list);
    }

    /**
     * Store user mgmt
     */
    public function store(array $payload): int
    {
        $id = $this->userMgmt->executeStore($payload);
        $this->recordHistory($id, ActionType::CREATE, $payload);

        return $id;
    }

    /**
     * Update user mgmt
     */
    public function update(array $payload): int
    {
        $id = $payload['id'];
        $affected = $this->userMgmt->executeUpdate($payload);
        $this->recordHistory($id, ActionType::UPDATE, $payload);

        return $affected;
    }

    /**
     * Delete user mgmt
     */
    public function delete(array $payload): void
    {
        if (! isset($payload['ids']) || ! is_array($payload['ids'])) {
            $this->userMgmt->executeDelete($payload['ids'] ?? []);

            return;
        }

        foreach ($payload['ids'] as $id) {
            $this->recordHistory($id, ActionType::DELETE, $payload);
        }

        $this->userMgmt->executeDelete($payload['ids']);
    }
}
