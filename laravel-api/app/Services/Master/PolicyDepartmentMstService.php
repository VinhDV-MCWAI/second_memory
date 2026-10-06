<?php

namespace App\Services\Master;

use App\Enums\ActionType;
use App\Http\Resources\Master\PolicyDepartmentMstResource;
use App\Interfaces\History\Master\PolicyDepartmentMstHistInterface;
use App\Interfaces\Master\PolicyDepartmentMstInterface;
use App\Services\BaseService;
use Illuminate\Http\Resources\Json\JsonResource;

class PolicyDepartmentMstService extends BaseService
{
    public function __construct(
        protected PolicyDepartmentMstInterface $policyDepartmentMst,
        protected PolicyDepartmentMstHistInterface $policyDepartmentMstHist
    ) {}

    protected function getHistoryRepository()
    {
        return $this->policyDepartmentMstHist;
    }

    protected function getHistoryForeignKey(): string
    {
        return 'policy_department_mst_id';
    }

    /**
     * Get policy department mst list
     */
    public function list(array $payload): JsonResource
    {
        $list = $this->policyDepartmentMst->list($payload);

        return PolicyDepartmentMstResource::collection($list);
    }

    /**
     * Store policy department mst
     */
    public function store(array $payload): int
    {
        $id = $this->policyDepartmentMst->executeStore($payload);
        $this->recordHistory($id, ActionType::CREATE, $payload);

        return $id;
    }

    /**
     * Update policy department mst
     */
    public function update(array $payload): int
    {
        $id = $payload['id'];
        $affected = $this->policyDepartmentMst->executeUpdate($payload);
        $this->recordHistory($id, ActionType::UPDATE, $payload);

        return $affected;
    }

    /**
     * Delete policy department mst
     */
    public function delete(array $payload): void
    {
        if (! isset($payload['ids']) || ! is_array($payload['ids'])) {
            $this->policyDepartmentMst->executeDelete($payload['ids'] ?? []);

            return;
        }

        foreach ($payload['ids'] as $id) {
            $this->recordHistory($id, ActionType::DELETE, $payload);
        }

        $this->policyDepartmentMst->executeDelete($payload['ids']);
    }
}
