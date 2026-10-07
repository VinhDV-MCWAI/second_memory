<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\ApiRoleMst\ListApiRoleMstRequest;
use App\Http\Requests\Master\ApiRoleMst\UpdateApiRoleMstRequest;
use App\Http\Resources\Master\ApiRoleMstResource;
use App\Services\Master\ApiRoleMstService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApiRoleMstController extends Controller
{
    public function __construct(
        protected ApiRoleMstService $apiRoleMst
    ) {}

    /**
     * ApiRoleMst list
     */
    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, ApiRoleMstResource>>
     */
    public function list(ListApiRoleMstRequest $request): AnonymousResourceCollection
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
