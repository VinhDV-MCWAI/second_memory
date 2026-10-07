<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\DepartmentManagementMst\ListDepartmentManagementMstRequest;
use App\Http\Requests\Master\DepartmentManagementMst\UpdateDepartmentManagementMstRequest;
use App\Http\Resources\Master\DepartmentManagementMstResource;
use App\Services\Master\DepartmentManagementMstService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DepartmentManagementMstController extends Controller
{
    public function __construct(
        protected DepartmentManagementMstService $departmentManagementMst
    ) {}

    /**
     * DepartmentManagementMst list
     */
    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, DepartmentManagementMstResource>>
     */
    public function list(ListDepartmentManagementMstRequest $request): AnonymousResourceCollection
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
