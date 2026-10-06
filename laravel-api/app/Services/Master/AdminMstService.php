<?php

namespace App\Services\Master;

use App\Enums\ActionType;
use App\Http\Resources\Master\AdminMstResource;
use App\Repositories\History\Master\AdminMstHistRepository;
use App\Repositories\Master\AdminMstRepository;
use App\Services\BaseService;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminMstService extends BaseService
{
    public function __construct(
        protected AdminMstRepository $adminMst,
        protected AdminMstHistRepository $adminMstHist
    ) {}

    protected function getHistoryRepository()
    {
        return $this->adminMstHist;
    }

    protected function getHistoryForeignKey(): string
    {
        return 'admin_mst_id';
    }

    /**
     * Get admin mst list
     */
    public function list(array $payload): JsonResource
    {
        $list = $this->adminMst->list($payload);

        return AdminMstResource::collection($list);
    }

    /**
     * Store admin mst
     */
    public function store(array $payload): int
    {
        $id = $this->adminMst->executeStore($payload);
        $this->recordHistory($id, ActionType::CREATE, $payload);

        return $id;
    }

    /**
     * Update admin mst
     */
    public function update(array $payload): int
    {
        $id = $payload['id'];
        $affected = $this->adminMst->executeUpdate($payload);
        $this->recordHistory($id, ActionType::UPDATE, $payload);

        return $affected;
    }

    /**
     * Delete admin mst
     */
    public function delete(array $payload): void
    {
        if (! isset($payload['ids']) || ! is_array($payload['ids'])) {
            $this->adminMst->executeDelete($payload['ids'] ?? []);

            return;
        }

        foreach ($payload['ids'] as $id) {
            $this->recordHistory($id, ActionType::DELETE, $payload);
        }

        $this->adminMst->executeDelete($payload['ids']);
    }
}
