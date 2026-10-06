<?php

namespace App\Services\History\Management;

use App\Http\Resources\History\Management\BannerMgmtHistResource;
use App\Repositories\History\Management\BannerMgmtHistRepository;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerMgmtHistService
{
    public function __construct(
        protected BannerMgmtHistRepository $bannerMgmtHist
    ) {}

    /**
     * Get banner mgmt hist list
     */
    public function list(array $payload): JsonResource
    {
        $list = $this->bannerMgmtHist->list($payload);

        return BannerMgmtHistResource::collection($list);
    }

    /**
     * Store banner mgmt hist
     */
    public function store(array $payload): int
    {
        return $this->bannerMgmtHist->executeStore($payload);
    }

    /**
     * Update banner mgmt hist
     */
    public function update(array $payload): int
    {
        return $this->bannerMgmtHist->executeUpdate($payload);
    }

    /**
     * Delete banner mgmt hist
     */
    public function delete(array $payload): void
    {
        $this->bannerMgmtHist->executeDelete($payload['ids']);
    }
}
