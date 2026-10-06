<?php

namespace App\Services\History\Master;

use App\Http\Resources\History\Master\ApiMstHistResource;
use App\Repositories\History\Master\ApiMstHistRepository;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiMstHistService
{
    public function __construct(
        protected ApiMstHistRepository $apiMstHist
    ) {}

    /**
     * Get api mst hist list
     */
    public function list(array $payload): JsonResource
    {
        $list = $this->apiMstHist->list($payload);

        return ApiMstHistResource::collection($list);
    }

    /**
     * Store api mst hist
     */
    public function store(array $payload): int
    {
        return $this->apiMstHist->executeStore($payload);
    }

    /**
     * Update api mst hist
     */
    public function update(array $payload): int
    {
        return $this->apiMstHist->executeUpdate($payload);
    }

    /**
     * Delete api mst hist
     */
    public function delete(array $payload): void
    {
        $this->apiMstHist->executeDelete($payload['ids']);
    }
}
