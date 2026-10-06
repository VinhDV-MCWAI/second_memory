<?php

namespace App\Http\Controllers\History\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\History\Master\RoleMstHist\DeleteRoleMstHistRequest;
use App\Http\Requests\History\Master\RoleMstHist\ListRoleMstHistRequest;
use App\Http\Requests\History\Master\RoleMstHist\StoreRoleMstHistRequest;
use App\Http\Requests\History\Master\RoleMstHist\UpdateRoleMstHistRequest;
use App\Services\History\Master\RoleMstHistService;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleMstHistController extends Controller
{
    public function __construct(
        protected RoleMstHistService $roleMstHist
    ) {}

    /**
     * RoleMstHist list
     */
    public function list(ListRoleMstHistRequest $request): JsonResource
    {
        return $this->roleMstHist->list($request->all());
    }

    /**
     * Store role mst hist
     */
    public function store(StoreRoleMstHistRequest $request): int
    {
        return $this->roleMstHist->store($request->all());
    }

    /**
     * Update role mst hist
     */
    public function update(UpdateRoleMstHistRequest $request, string $id): int
    {
        $payload = $request->all();
        $payload['id'] = $id;

        return $this->roleMstHist->update($payload);
    }

    /**
     * Delete role mst hist
     */
    public function delete(DeleteRoleMstHistRequest $request): void
    {
        $this->roleMstHist->delete($request->all());
    }
}
