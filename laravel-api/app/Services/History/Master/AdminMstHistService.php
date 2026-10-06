<?php

namespace App\Services\History\Master;

use App\Http\Resources\History\Master\AdminMstHistResource;
use App\Repositories\History\Master\AdminMstHistRepository;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminMstHistService
{
    public function __construct(
        protected AdminMstHistRepository $adminMstHist
    ) {}

    /**
     * Get admin mst hist list
     */
    public function list(array $payload): JsonResource
    {
        $list = $this->adminMstHist->list($payload);

        return AdminMstHistResource::collection($list);
    }

    /**
     * Store admin mst hist
     */
    public function store(array $payload): int
    {
        return $this->adminMstHist->executeStore($payload);
    }

    /**
     * Update admin mst hist
     */
    public function update(array $payload): int
    {
        return $this->adminMstHist->executeUpdate($payload);
    }

    /**
     * Delete admin mst hist
     */
    public function delete(array $payload): void
    {
        $this->adminMstHist->executeDelete($payload['ids']);
    }
}
