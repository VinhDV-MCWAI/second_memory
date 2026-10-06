<?php

namespace App\Services\Master;

use App\Enums\ActionType;
use App\Http\Resources\Master\DepartmentMstResource;
use App\Interfaces\History\Master\DepartmentMstHistInterface;
use App\Interfaces\Master\DepartmentMstInterface;
use App\Services\BaseService;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentMstService extends BaseService
{
    public function __construct(
        protected DepartmentMstInterface $departmentMst,
        protected DepartmentMstHistInterface $departmentMstHist
    ) {}

    protected function getHistoryRepository()
    {
        return $this->departmentMstHist;
    }

    protected function getHistoryForeignKey(): string
    {
        return 'department_mst_id';
    }

    /**
     * Get department mst list
     */
    public function list(array $payload): JsonResource
    {
        $list = $this->departmentMst->list($payload);

        return DepartmentMstResource::collection($list);
    }

    /**
     * Store department mst
     */
    public function store(array $payload): int
    {
        $id = $this->departmentMst->executeStore($payload);
        $this->recordHistory($id, ActionType::CREATE, $payload);

        return $id;
    }

    /**
     * Update department mst
     */
    public function update(array $payload): int
    {
        $id = $payload['id'];
        $affected = $this->departmentMst->executeUpdate($payload);
        $this->recordHistory($id, ActionType::UPDATE, $payload);

        return $affected;
    }

    /**
     * Delete department mst
     */
    public function delete(array $payload): void
    {
        if (! isset($payload['ids']) || ! is_array($payload['ids'])) {
            $this->departmentMst->executeDelete($payload['ids'] ?? []);

            return;
        }

        foreach ($payload['ids'] as $id) {
            $this->recordHistory($id, ActionType::DELETE, $payload);
        }

        $this->departmentMst->executeDelete($payload['ids']);
    }
}
