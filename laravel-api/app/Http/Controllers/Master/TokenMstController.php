<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\TokenMst\DeleteTokenMstRequest;
use App\Http\Requests\Master\TokenMst\ListTokenMstRequest;
use App\Http\Requests\Master\TokenMst\StoreTokenMstRequest;
use App\Http\Requests\Master\TokenMst\UpdateTokenMstRequest;
use App\Http\Resources\Master\TokenMstResource;
use App\Services\Master\TokenMstService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TokenMstController extends Controller
{
    public function __construct(
        protected TokenMstService $tokenMst
    ) {}

    /**
     * TokenMst list
     */
    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, TokenMstResource>>
     */
    public function list(ListTokenMstRequest $request): AnonymousResourceCollection
    {
        return $this->tokenMst->list($request->validated());
    }

    /**
     * Store token mst
     */
    public function store(StoreTokenMstRequest $request): int
    {
        return $this->tokenMst->store($request->validated());
    }

    /**
     * Update token mst
     */
    public function update(UpdateTokenMstRequest $request, string $id): int
    {
        $payload = $request->validated();
        $payload['id'] = $id;

        return $this->tokenMst->update($payload);
    }

    /**
     * Delete token mst
     */
    public function delete(DeleteTokenMstRequest $request): void
    {
        $this->tokenMst->delete($request->validated());
    }
}
