<?php

namespace App\Http\Controllers\History\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\History\Master\DepartmentMstHist\DeleteDepartmentMstHistRequest;
use App\Http\Requests\History\Master\DepartmentMstHist\ListDepartmentMstHistRequest;
use App\Http\Requests\History\Master\DepartmentMstHist\StoreDepartmentMstHistRequest;
use App\Http\Requests\History\Master\DepartmentMstHist\UpdateDepartmentMstHistRequest;
use App\Services\History\Master\DepartmentMstHistService;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentMstHistController extends Controller
{
    public function __construct(
        protected DepartmentMstHistService $departmentMstHist
    ) {}

    /**
     * DepartmentMstHist list
     */
    public function list(ListDepartmentMstHistRequest $request): JsonResource
    {
        return $this->departmentMstHist->list($request->validated());
    }

    /**
     * Store department mst hist
     */
    public function store(StoreDepartmentMstHistRequest $request): int
    {
        return $this->departmentMstHist->store($request->validated());
    }

    /**
     * Update department mst hist
     */
    public function update(UpdateDepartmentMstHistRequest $request, string $id): int
    {
        $payload = $request->validated();
        $payload['id'] = $id;

        return $this->departmentMstHist->update($payload);
    }

    /**
     * Delete department mst hist
     */
    public function delete(DeleteDepartmentMstHistRequest $request): void
    {
        $this->departmentMstHist->delete($request->validated());
    }
}
