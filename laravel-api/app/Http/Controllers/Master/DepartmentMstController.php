<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\DepartmentMst\DeleteDepartmentMstRequest;
use App\Http\Requests\Master\DepartmentMst\ListDepartmentMstRequest;
use App\Http\Requests\Master\DepartmentMst\StoreDepartmentMstRequest;
use App\Http\Requests\Master\DepartmentMst\UpdateDepartmentMstRequest;
use App\Services\Master\DepartmentMstService;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentMstController extends Controller
{
    public function __construct(
        protected DepartmentMstService $departmentMst
    ) {}

    /**
     * DepartmentMst list
     */
    public function list(ListDepartmentMstRequest $request): JsonResource
    {
        return $this->departmentMst->list($request->all());
    }

    /**
     * Store department mst
     */
    public function store(StoreDepartmentMstRequest $request): int
    {
        return $this->departmentMst->store($request->all());
    }

    /**
     * Update department mst
     */
    public function update(UpdateDepartmentMstRequest $request, string $id): int
    {
        $payload = $request->all();
        $payload['id'] = $id;

        return $this->departmentMst->update($payload);
    }

    /**
     * Delete department mst
     */
    public function delete(DeleteDepartmentMstRequest $request): void
    {
        $this->departmentMst->delete($request->all());
    }
}
