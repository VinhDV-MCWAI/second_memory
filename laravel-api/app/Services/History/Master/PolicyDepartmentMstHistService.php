<?php

namespace App\Services\History\Master;

use App\Http\Resources\History\Master\PolicyDepartmentMstHistResource;
use App\Repositories\History\Master\PolicyDepartmentMstHistRepository;
use Illuminate\Http\Resources\Json\JsonResource;

class PolicyDepartmentMstHistService
{
    public function __construct(
        protected PolicyDepartmentMstHistRepository $policyDepartmentMstHist
    ) {}

    /**
     * Get policy department mst hist list
     */
    public function list(array $payload): JsonResource
    {
        $list = $this->policyDepartmentMstHist->list($payload);

        return PolicyDepartmentMstHistResource::collection($list);
    }

    /**
     * Store policy department mst hist
     */
    public function store(array $payload): int
    {
        return $this->policyDepartmentMstHist->executeStore($payload);
    }

    /**
     * Update policy department mst hist
     */
    public function update(array $payload): int
    {
        return $this->policyDepartmentMstHist->executeUpdate($payload);
    }

    /**
     * Delete policy department mst hist
     */
    public function delete(array $payload): void
    {
        $this->policyDepartmentMstHist->executeDelete($payload['ids']);
    }
}
