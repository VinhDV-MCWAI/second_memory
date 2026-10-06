<?php

namespace App\Services\History\Master;

use App\Http\Resources\History\Master\FeatureMstHistResource;
use App\Repositories\History\Master\FeatureMstHistRepository;
use Illuminate\Http\Resources\Json\JsonResource;

class FeatureMstHistService
{
    public function __construct(
        protected FeatureMstHistRepository $featureMstHist
    ) {}

    /**
     * Get feature mst hist list
     */
    public function list(array $payload): JsonResource
    {
        $list = $this->featureMstHist->list($payload);

        return FeatureMstHistResource::collection($list);
    }

    /**
     * Store feature mst hist
     */
    public function store(array $payload): int
    {
        return $this->featureMstHist->executeStore($payload);
    }

    /**
     * Update feature mst hist
     */
    public function update(array $payload): int
    {
        return $this->featureMstHist->executeUpdate($payload);
    }

    /**
     * Delete feature mst hist
     */
    public function delete(array $payload): void
    {
        $this->featureMstHist->executeDelete($payload['ids']);
    }
}
