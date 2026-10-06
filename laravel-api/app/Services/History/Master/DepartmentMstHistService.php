<?php

namespace App\Services\History\Master;

use App\Http\Resources\History\Master\DepartmentMstHistResource;
use App\Repositories\History\Master\DepartmentMstHistRepository;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentMstHistService
{
    public function __construct(
        protected DepartmentMstHistRepository $departmentMstHist
    ) {}

    /**
     * Get department mst hist list
     */
    public function list(array $payload): JsonResource
    {
        $list = $this->departmentMstHist->list($payload);

        return DepartmentMstHistResource::collection($list);
    }

    /**
     * Store department mst hist
     */
    public function store(array $payload): int
    {
        return $this->departmentMstHist->executeStore($payload);
    }

    /**
     * Update department mst hist
     */
    public function update(array $payload): int
    {
        return $this->departmentMstHist->executeUpdate($payload);
    }

    /**
     * Delete department mst hist
     */
    public function delete(array $payload): void
    {
        $this->departmentMstHist->executeDelete($payload['ids']);
    }
}
