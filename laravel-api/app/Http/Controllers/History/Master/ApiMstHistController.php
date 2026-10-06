<?php

declare(strict_types=1);

namespace App\Http\Controllers\History\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\History\Master\ApiMstHist\DeleteApiMstHistRequest;
use App\Http\Requests\History\Master\ApiMstHist\ListApiMstHistRequest;
use App\Http\Requests\History\Master\ApiMstHist\StoreApiMstHistRequest;
use App\Http\Requests\History\Master\ApiMstHist\UpdateApiMstHistRequest;
use App\Http\Resources\History\Master\ApiMstHistResource;
use App\Services\History\Master\ApiMstHistService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApiMstHistController extends Controller
{
    public function __construct(
        protected ApiMstHistService $apiMstHist
    ) {}

    /**
     * ApiMstHist list
     */
    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, ApiMstHistResource>>
     */
    public function list(ListApiMstHistRequest $request): AnonymousResourceCollection
    {
        return $this->apiMstHist->list($request->validated());
    }

    /**
     * Store api mst hist
     */
    public function store(StoreApiMstHistRequest $request): int
    {
        return $this->apiMstHist->store($request->validated());
    }

    /**
     * Update api mst hist
     */
    public function update(UpdateApiMstHistRequest $request, string $id): int
    {
        $payload = $request->validated();
        $payload['id'] = $id;

        return $this->apiMstHist->update($payload);
    }

    /**
     * Delete api mst hist
     */
    public function delete(DeleteApiMstHistRequest $request): void
    {
        $this->apiMstHist->delete($request->validated());
    }
}
