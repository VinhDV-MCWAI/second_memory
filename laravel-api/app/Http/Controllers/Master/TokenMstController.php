<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\TokenMst\DeleteTokenMstRequest;
use App\Http\Requests\Master\TokenMst\ListTokenMstRequest;
use App\Http\Requests\Master\TokenMst\StoreTokenMstRequest;
use App\Http\Requests\Master\TokenMst\UpdateTokenMstRequest;
use App\Services\Master\TokenMstService;
use Illuminate\Http\Resources\Json\JsonResource;

class TokenMstController extends Controller
{
    public function __construct(
        protected TokenMstService $tokenMst
    ) {}

    /**
     * TokenMst list
     */
    public function list(ListTokenMstRequest $request): JsonResource
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
