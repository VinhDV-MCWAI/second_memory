<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\Department\DeleteDepartmentRequest;
use App\Http\Requests\Master\Department\ListDepartmentRequest;
use App\Http\Requests\Master\Department\StoreDepartmentRequest;
use App\Http\Requests\Master\Department\UpdateDepartmentRequest;
use App\Http\Resources\Master\DepartmentResource;
use App\Services\Master\DepartmentService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DepartmentController extends Controller
{
    public function __construct(private readonly DepartmentService $departments) {}

    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, DepartmentResource>>
     */
    public function list(ListDepartmentRequest $request): AnonymousResourceCollection
    {
        return $this->departments->list($request->validated());
    }

    public function store(StoreDepartmentRequest $request): int
    {
        return $this->departments->store($request->validated());
    }

    public function update(UpdateDepartmentRequest $request, string $id): int
    {
        return $this->departments->update([...$request->validated(), 'id' => $id]);
    }

    public function delete(DeleteDepartmentRequest $request): void
    {
        $this->departments->delete($request->validated());
    }
}
