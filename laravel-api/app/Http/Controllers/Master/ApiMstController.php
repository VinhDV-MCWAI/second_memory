<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\ApiMst\DeleteApiMstRequest;
use App\Http\Requests\Master\ApiMst\ListApiMstRequest;
use App\Http\Requests\Master\ApiMst\StoreApiMstRequest;
use App\Http\Requests\Master\ApiMst\UpdateApiMstRequest;
use App\Services\Master\ApiMstService;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiMstController extends Controller
{
    public function __construct(
        protected ApiMstService $apiMst
    ) {}

    /**
     * ApiMst list
     */
    public function list(ListApiMstRequest $request): JsonResource
    {
        return $this->apiMst->list($request->all());
    }

    /**
     * Store api mst
     */
    public function store(StoreApiMstRequest $request): int
    {
        return $this->apiMst->store($request->all());
    }

    /**
     * Update api mst
     */
    public function update(UpdateApiMstRequest $request, string $id): int
    {
        $payload = $request->all();
        $payload['id'] = $id;

        return $this->apiMst->update($payload);
    }

    /**
     * Delete api mst
     */
    public function delete(DeleteApiMstRequest $request): void
    {
        $this->apiMst->delete($request->all());
    }
}
