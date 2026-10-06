<?php

namespace App\Services\History\Master;

use App\Http\Resources\History\Master\RoleMstHistResource;
use App\Interfaces\History\Master\RoleMstHistInterface;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleMstHistService
{
    public function __construct(
        protected RoleMstHistInterface $roleMstHist
    ) {}

    /**
     * Get role mst hist list
     */
    public function list(array $payload): JsonResource
    {
        $list = $this->roleMstHist->list($payload);

        return RoleMstHistResource::collection($list);
    }

    /**
     * Store role mst hist
     */
    public function store(array $payload): int
    {
        return $this->roleMstHist->executeStore($payload);
    }

    /**
     * Update role mst hist
     */
    public function update(array $payload): int
    {
        return $this->roleMstHist->executeUpdate($payload);
    }

    /**
     * Delete role mst hist
     */
    public function delete(array $payload): void
    {
        $this->roleMstHist->executeDelete($payload['ids']);
    }
}
