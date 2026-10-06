<?php

declare(strict_types=1);

namespace App\Services\Master;

use App\Http\Resources\Master\AdminDepartmentMstResource;
use App\Repositories\Master\AdminDepartmentMstRepository;
use App\Services\BaseJunctionService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminDepartmentMstService extends BaseJunctionService
{
    public function __construct(
        protected AdminDepartmentMstRepository $adminDepartmentMst
    ) {}

    /**
     * Get admin department mst list
     */
    public function list(array $payload): AnonymousResourceCollection
    {
        $list = $this->adminDepartmentMst->list($payload);

        return AdminDepartmentMstResource::collection($list);
    }

    /**
     * Update admin department mst
     */
    public function update(array $payload): bool
    {
        // Delete admin department mst
        if (! empty($payload['delete'])) {
            $this->validateExistence(
                $payload['delete'],
                fn ($values) => $this->adminDepartmentMst->getAdminDepartmentMstId($values),
                'admin_department_id',
                'admin_department_mst'
            );
            $this->adminDepartmentMst->executeDelete($payload['delete']);
        }

        // Insert admin department mst
        if (! empty($payload['insert'])) {
            $this->validateNonExistence(
                $payload['insert'],
                fn ($values) => $this->adminDepartmentMst->getAdminDepartmentMstId($values),
                'admin_department_id',
                'admin_department_mst'
            );
            $this->adminDepartmentMst->executeStore($payload['insert']);
        }

        return true;
    }
}
