<?php

namespace App\Services\Master;

use App\Http\Resources\Master\TokenMstResource;
use App\Repositories\Master\TokenMstRepository;
use App\Services\BaseService;
use Illuminate\Http\Resources\Json\JsonResource;

class TokenMstService extends BaseService
{
    public function __construct(
        protected TokenMstRepository $tokenMst
    ) {}

    protected function getHistoryRepository()
    {
        return null;
    }

    protected function getHistoryForeignKey(): string
    {
        return 'token_mst_id';
    }

    /**
     * Get token mst list
     */
    public function list(array $payload): JsonResource
    {
        $list = $this->tokenMst->list($payload);

        return TokenMstResource::collection($list);
    }

    /**
     * Store token mst
     */
    public function store(array $payload): int
    {
        $id = $this->tokenMst->executeStore($payload);

        return $id;
    }

    /**
     * Update token mst
     */
    public function update(array $payload): int
    {
        $id = $payload['id'];
        $affected = $this->tokenMst->executeUpdate($payload);

        return $affected;
    }

    /**
     * Delete token mst
     */
    public function delete(array $payload): void
    {
        if (! isset($payload['ids']) || ! is_array($payload['ids'])) {
            $this->tokenMst->executeDelete($payload['ids'] ?? []);

            return;
        }

        $this->tokenMst->executeDelete($payload['ids']);
    }
}
