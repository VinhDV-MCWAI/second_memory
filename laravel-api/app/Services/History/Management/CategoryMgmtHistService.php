<?php

namespace App\Services\History\Management;

use App\Http\Resources\History\Management\CategoryMgmtHistResource;
use App\Repositories\History\Management\CategoryMgmtHistRepository;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryMgmtHistService
{
    public function __construct(
        protected CategoryMgmtHistRepository $categoryMgmtHist
    ) {}

    /**
     * Get category mgmt hist list
     */
    public function list(array $payload): JsonResource
    {
        $list = $this->categoryMgmtHist->list($payload);

        return CategoryMgmtHistResource::collection($list);
    }

    /**
     * Store category mgmt hist
     */
    public function store(array $payload): int
    {
        return $this->categoryMgmtHist->executeStore($payload);
    }

    /**
     * Update category mgmt hist
     */
    public function update(array $payload): int
    {
        return $this->categoryMgmtHist->executeUpdate($payload);
    }

    /**
     * Delete category mgmt hist
     */
    public function delete(array $payload): void
    {
        $this->categoryMgmtHist->executeDelete($payload['ids']);
    }
}
