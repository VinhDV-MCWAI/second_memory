<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\ApiRoleMst\ListApiRoleMstRequest;
use App\Http\Requests\Master\ApiRoleMst\UpdateApiRoleMstRequest;
use App\Services\Master\ApiRoleMstService;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiRoleMstController extends Controller
{
    public function __construct(
        protected ApiRoleMstService $apiRoleMst
    ) {}

    /**
     * ApiRoleMst list
     */
    public function list(ListApiRoleMstRequest $request): JsonResource
    {
        return $this->apiRoleMst->list($request->validated());
    }

    /**
     * Update api role mst
     */
    public function update(UpdateApiRoleMstRequest $request): bool
    {
        return $this->apiRoleMst->update($request->validated());
    }
}
