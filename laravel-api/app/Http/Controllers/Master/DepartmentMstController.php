<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\DepartmentMst\DeleteDepartmentMstRequest;
use App\Http\Requests\Master\DepartmentMst\ListDepartmentMstRequest;
use App\Http\Requests\Master\DepartmentMst\StoreDepartmentMstRequest;
use App\Http\Requests\Master\DepartmentMst\UpdateDepartmentMstRequest;
use App\Http\Resources\Master\DepartmentMstResource;
use App\Services\Master\DepartmentMstService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DepartmentMstController extends Controller
{
    public function __construct(
        protected DepartmentMstService $departmentMst
    ) {}

    /**
     * DepartmentMst list
     */
    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, DepartmentMstResource>>
     */
    public function list(ListDepartmentMstRequest $request): AnonymousResourceCollection
    {
        return $this->departmentMst->list($request->validated());
    }

    /**
     * Store department mst
     */
    public function store(StoreDepartmentMstRequest $request): int
    {
        return $this->departmentMst->store($request->validated());
    }

    /**
     * Update department mst
     */
    public function update(UpdateDepartmentMstRequest $request, string $id): int
    {
        $payload = $request->validated();
        $payload['id'] = $id;

        return $this->departmentMst->update($payload);
    }

    /**
     * Delete department mst
     */
    public function delete(DeleteDepartmentMstRequest $request): void
    {
        $this->departmentMst->delete($request->validated());
    }
}
