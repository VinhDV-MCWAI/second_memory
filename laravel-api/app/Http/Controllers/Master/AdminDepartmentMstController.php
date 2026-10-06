<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\AdminDepartmentMst\ListAdminDepartmentMstRequest;
use App\Http\Requests\Master\AdminDepartmentMst\UpdateAdminDepartmentMstRequest;
use App\Services\Master\AdminDepartmentMstService;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminDepartmentMstController extends Controller
{
    public function __construct(
        protected AdminDepartmentMstService $adminDepartmentMst
    ) {}

    /**
     * AdminDepartmentMst list
     */
    public function list(ListAdminDepartmentMstRequest $request): JsonResource
    {
        return $this->adminDepartmentMst->list($request->validated());
    }

    /**
     * Update admin department mst
     */
    public function update(UpdateAdminDepartmentMstRequest $request): bool
    {
        return $this->adminDepartmentMst->update($request->validated());
    }
}
