<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\ApiMst\DeleteApiMstRequest;
use App\Http\Requests\Master\ApiMst\ListApiMstRequest;
use App\Http\Requests\Master\ApiMst\StoreApiMstRequest;
use App\Http\Requests\Master\ApiMst\UpdateApiMstRequest;
use App\Http\Resources\Master\ApiMstResource;
use App\Services\Master\ApiMstService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApiMstController extends Controller
{
    public function __construct(
        protected ApiMstService $apiMst
    ) {}

    /**
     * ApiMst list
     */
    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, ApiMstResource>>
     */
    public function list(ListApiMstRequest $request): AnonymousResourceCollection
    {
        return $this->apiMst->list($request->validated());
    }

    /**
     * Store api mst
     */
    public function store(StoreApiMstRequest $request): int
    {
        return $this->apiMst->store($request->validated());
    }

    /**
     * Update api mst
     */
    public function update(UpdateApiMstRequest $request, string $id): int
    {
        $payload = $request->validated();
        $payload['id'] = $id;

        return $this->apiMst->update($payload);
    }

    /**
     * Delete api mst
     */
    public function delete(DeleteApiMstRequest $request): void
    {
        $this->apiMst->delete($request->validated());
    }
}
