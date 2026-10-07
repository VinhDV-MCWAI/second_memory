<?php

declare(strict_types=1);

namespace App\Services\Master;

use App\Http\Resources\Master\DepartmentManagementMstResource;
use App\Repositories\Master\DepartmentManagementMstRepository;
use App\Services\BaseJunctionService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DepartmentManagementMstService extends BaseJunctionService
{
    public function __construct(
        protected DepartmentManagementMstRepository $departmentManagementMst
    ) {}

    /**
     * Get department management mst list
     */
    public function list(array $payload): AnonymousResourceCollection
    {
        $list = $this->departmentManagementMst->list($payload);

        return DepartmentManagementMstResource::collection($list);
    }

    /**
     * Update department management mst
     */
    public function update(array $payload): bool
    {
        // Delete department management mst
        if (! empty($payload['delete'])) {
            $this->validateExistence(
                $payload['delete'],
                fn ($values) => $this->departmentManagementMst->getDepartmentManagementMstId($values),
                'department_management_id',
                'department_management_mst'
            );
            $this->departmentManagementMst->executeDelete($payload['delete']);
        }

        // Insert department management mst
        if (! empty($payload['insert'])) {
            $this->validateNonExistence(
                $payload['insert'],
                fn ($values) => $this->departmentManagementMst->getDepartmentManagementMstId($values),
                'department_management_id',
                'department_management_mst'
            );
            $this->departmentManagementMst->executeStore($payload['insert']);
        }

        return true;
    }
}
