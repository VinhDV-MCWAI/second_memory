<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\AdminDepartmentMst\ListAdminDepartmentMstRequest;
use App\Http\Requests\Master\AdminDepartmentMst\UpdateAdminDepartmentMstRequest;
use App\Http\Resources\Master\AdminDepartmentMstResource;
use App\Services\Master\AdminDepartmentMstService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminDepartmentMstController extends Controller
{
    public function __construct(
        protected AdminDepartmentMstService $adminDepartmentMst
    ) {}

    /**
     * AdminDepartmentMst list
     */
    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, AdminDepartmentMstResource>>
     */
    public function list(ListAdminDepartmentMstRequest $request): AnonymousResourceCollection
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
