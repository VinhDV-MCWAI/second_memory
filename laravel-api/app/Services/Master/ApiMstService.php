<?php

namespace App\Services\Master;

use App\Enums\ActionType;
use App\Http\Resources\Master\ApiMstResource;
use App\Repositories\History\Master\ApiMstHistRepository;
use App\Repositories\Master\ApiMstRepository;
use App\Services\BaseService;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiMstService extends BaseService
{
    public function __construct(
        protected ApiMstRepository $apiMst,
        protected ApiMstHistRepository $apiMstHist
    ) {}

    protected function getHistoryRepository()
    {
        return $this->apiMstHist;
    }

    protected function getHistoryForeignKey(): string
    {
        return 'api_mst_id';
    }

    /**
     * Get api mst list
     */
    public function list(array $payload): JsonResource
    {
        $list = $this->apiMst->list($payload);

        return ApiMstResource::collection($list);
    }

    /**
     * Store api mst
     */
    public function store(array $payload): int
    {
        $id = $this->apiMst->executeStore($payload);
        $this->recordHistory($id, ActionType::CREATE, $payload);

        return $id;
    }

    /**
     * Update api mst
     */
    public function update(array $payload): int
    {
        $id = $payload['id'];
        $affected = $this->apiMst->executeUpdate($payload);
        $this->recordHistory($id, ActionType::UPDATE, $payload);

        return $affected;
    }

    /**
     * Delete api mst
     */
    public function delete(array $payload): void
    {
        // Ensure ids is present and is an array
        if (! isset($payload['ids']) || ! is_array($payload['ids'])) {
            return; // Nothing to delete
        }

        // Record history for each ID before deletion
        foreach ($payload['ids'] as $id) {
            $this->recordHistory($id, ActionType::DELETE, $payload);
        }

        // Execute batch soft delete
        $this->apiMst->executeDelete($payload['ids']);
    }
}
