<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\RoleMst\DeleteRoleMstRequest;
use App\Http\Requests\Master\RoleMst\ListRoleMstRequest;
use App\Http\Requests\Master\RoleMst\StoreRoleMstRequest;
use App\Http\Requests\Master\RoleMst\UpdateRoleMstRequest;
use App\Services\Master\RoleMstService;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleMstController extends Controller
{
    public function __construct(
        protected RoleMstService $roleMst
    ) {}

    /**
     * RoleMst list
     */
    public function list(ListRoleMstRequest $request): JsonResource
    {
        return $this->roleMst->list($request->validated());
    }

    /**
     * Store role mst
     */
    public function store(StoreRoleMstRequest $request): int
    {
        return $this->roleMst->store($request->validated());
    }

    /**
     * Update role mst
     */
    public function update(UpdateRoleMstRequest $request, string $id): int
    {
        $payload = $request->validated();
        $payload['id'] = $id;

        return $this->roleMst->update($payload);
    }

    /**
     * Delete role mst
     */
    public function delete(DeleteRoleMstRequest $request): void
    {
        $this->roleMst->delete($request->validated());
    }
}
