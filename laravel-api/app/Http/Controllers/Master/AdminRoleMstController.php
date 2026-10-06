<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\AdminRoleMst\ListAdminRoleMstRequest;
use App\Http\Requests\Master\AdminRoleMst\UpdateAdminRoleMstRequest;
use App\Services\Master\AdminRoleMstService;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminRoleMstController extends Controller
{
    public function __construct(
        protected AdminRoleMstService $adminRoleMst
    ) {}

    /**
     * AdminRoleMst list
     */
    public function list(ListAdminRoleMstRequest $request): JsonResource
    {
        return $this->adminRoleMst->list($request->validated());
    }

    /**
     * Update admin role mst
     */
    public function update(UpdateAdminRoleMstRequest $request): bool
    {
        return $this->adminRoleMst->update($request->validated());
    }
}
