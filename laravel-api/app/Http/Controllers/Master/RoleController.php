<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\Role\DeleteRoleRequest;
use App\Http\Requests\Master\Role\ListRoleRequest;
use App\Http\Requests\Master\Role\StoreRoleRequest;
use App\Http\Requests\Master\Role\UpdateRoleRequest;
use App\Http\Resources\Master\RoleResource;
use App\Services\Master\RoleService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoleController extends Controller
{
    public function __construct(private readonly RoleService $roles) {}

    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, RoleResource>>
     */
    public function list(ListRoleRequest $request): AnonymousResourceCollection
    {
        return $this->roles->list($request->validated());
    }

    public function store(StoreRoleRequest $request): int
    {
        return $this->roles->store($request->validated());
    }

    public function update(UpdateRoleRequest $request, string $id): int
    {
        return $this->roles->update([...$request->validated(), 'id' => $id]);
    }

    public function delete(DeleteRoleRequest $request): void
    {
        $this->roles->delete($request->validated());
    }
}
