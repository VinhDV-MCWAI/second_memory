<?php

namespace App\Services\Master;

use App\Enums\ActionType;
use App\Http\Resources\Master\RoleMstResource;
use App\Repositories\History\Master\RoleMstHistRepository;
use App\Repositories\Master\RoleMstRepository;
use App\Services\BaseService;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleMstService extends BaseService
{
    public function __construct(
        protected RoleMstRepository $roleMst,
        protected RoleMstHistRepository $roleMstHist
    ) {}

    protected function getHistoryRepository()
    {
        return $this->roleMstHist;
    }

    protected function getHistoryForeignKey(): string
    {
        return 'role_mst_id';
    }

    /**
     * Get role mst list
     */
    public function list(array $payload): JsonResource
    {
        $list = $this->roleMst->list($payload);

        return RoleMstResource::collection($list);
    }

    /**
     * Store role mst
     */
    public function store(array $payload): int
    {
        $id = $this->roleMst->executeStore($payload);
        $this->recordHistory($id, ActionType::CREATE, $payload);

        return $id;
    }

    /**
     * Update role mst
     */
    public function update(array $payload): int
    {
        $id = $payload['id'];
        $affected = $this->roleMst->executeUpdate($payload);
        $this->recordHistory($id, ActionType::UPDATE, $payload);

        return $affected;
    }

    /**
     * Delete role mst
     */
    public function delete(array $payload): void
    {
        if (! isset($payload['ids']) || ! is_array($payload['ids'])) {
            $this->roleMst->executeDelete($payload['ids'] ?? []);

            return;
        }

        foreach ($payload['ids'] as $id) {
            $this->recordHistory($id, ActionType::DELETE, $payload);
        }

        $this->roleMst->executeDelete($payload['ids']);
    }
}
