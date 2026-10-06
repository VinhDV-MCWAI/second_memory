<?php

namespace App\Services\Management;

use App\Enums\ActionType;
use App\Http\Resources\Management\BannerMgmtResource;
use App\Interfaces\History\Management\BannerMgmtHistInterface;
use App\Interfaces\Management\BannerMgmtInterface;
use App\Services\BaseService;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerMgmtService extends BaseService
{
    public function __construct(
        protected BannerMgmtInterface $bannerMgmt,
        protected BannerMgmtHistInterface $bannerMgmtHist,
        protected MediaMgmtService $mediaService
    ) {}

    protected function getHistoryRepository()
    {
        return $this->bannerMgmtHist;
    }

    protected function getHistoryForeignKey(): string
    {
        return 'banner_mgmt_id';
    }

    /**
     * Get banner mgmt list
     */
    public function list(array $payload): JsonResource
    {
        $list = $this->bannerMgmt->list($payload);

        return BannerMgmtResource::collection($list);
    }

    /**
     * Store banner mgmt
     */
    public function store(array $payload): int
    {
        $id = $this->bannerMgmt->executeStore($payload);
        $this->recordHistory($id, ActionType::CREATE, $payload);

        return $id;
    }

    /**
     * Update banner mgmt
     */
    public function update(array $payload): int
    {
        $id = $payload['id'];
        $affected = $this->bannerMgmt->executeUpdate($payload);
        $this->recordHistory($id, ActionType::UPDATE, $payload);

        return $affected;
    }

    /**
     * Delete banner mgmt
     */
    public function delete(array $payload): void
    {
        if (! isset($payload['ids']) || ! is_array($payload['ids'])) {
            $this->bannerMgmt->executeDelete($payload['ids'] ?? []);

            return;
        }

        foreach ($payload['ids'] as $id) {
            $this->recordHistory($id, ActionType::DELETE, $payload);
        }

        $this->bannerMgmt->executeDelete($payload['ids']);
    }
}
