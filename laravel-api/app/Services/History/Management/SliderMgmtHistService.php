<?php

namespace App\Services\History\Management;

use App\Http\Resources\History\Management\SliderMgmtHistResource;
use App\Interfaces\History\Management\SliderMgmtHistInterface;
use Illuminate\Http\Resources\Json\JsonResource;

class SliderMgmtHistService
{
    public function __construct(
        protected SliderMgmtHistInterface $sliderMgmtHist
    ) {}

    /**
     * Get slider mgmt hist list
     */
    public function list(array $payload): JsonResource
    {
        $list = $this->sliderMgmtHist->list($payload);

        return SliderMgmtHistResource::collection($list);
    }

    /**
     * Store slider mgmt hist
     */
    public function store(array $payload): int
    {
        return $this->sliderMgmtHist->executeStore($payload);
    }

    /**
     * Update slider mgmt hist
     */
    public function update(array $payload): int
    {
        return $this->sliderMgmtHist->executeUpdate($payload);
    }

    /**
     * Delete slider mgmt hist
     */
    public function delete(array $payload): void
    {
        $this->sliderMgmtHist->executeDelete($payload['ids']);
    }
}
