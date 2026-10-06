<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\DepartmentManagementMst\ListDepartmentManagementMstRequest;
use App\Http\Requests\Master\DepartmentManagementMst\UpdateDepartmentManagementMstRequest;
use App\Services\Master\DepartmentManagementMstService;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentManagementMstController extends Controller
{
    public function __construct(
        protected DepartmentManagementMstService $departmentManagementMst
    ) {}

    /**
     * DepartmentManagementMst list
     */
    public function list(ListDepartmentManagementMstRequest $request): JsonResource
    {
        return $this->departmentManagementMst->list($request->validated());
    }

    /**
     * Update department management mst
     */
    public function update(UpdateDepartmentManagementMstRequest $request): bool
    {
        return $this->departmentManagementMst->update($request->validated());
    }
}
