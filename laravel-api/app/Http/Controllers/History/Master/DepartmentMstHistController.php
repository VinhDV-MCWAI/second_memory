<?php

declare(strict_types=1);

namespace App\Http\Controllers\History\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\History\Master\DepartmentMstHist\DeleteDepartmentMstHistRequest;
use App\Http\Requests\History\Master\DepartmentMstHist\ListDepartmentMstHistRequest;
use App\Http\Requests\History\Master\DepartmentMstHist\StoreDepartmentMstHistRequest;
use App\Http\Requests\History\Master\DepartmentMstHist\UpdateDepartmentMstHistRequest;
use App\Http\Resources\History\Master\DepartmentMstHistResource;
use App\Services\History\Master\DepartmentMstHistService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DepartmentMstHistController extends Controller
{
    public function __construct(
        protected DepartmentMstHistService $departmentMstHist
    ) {}

    /**
     * DepartmentMstHist list
     */
    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, DepartmentMstHistResource>>
     */
    public function list(ListDepartmentMstHistRequest $request): AnonymousResourceCollection
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
