<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\AdminMst\DeleteAdminMstRequest;
use App\Http\Requests\Master\AdminMst\ListAdminMstRequest;
use App\Http\Requests\Master\AdminMst\StoreAdminMstRequest;
use App\Http\Requests\Master\AdminMst\UpdateAdminMstRequest;
use App\Services\Master\AdminMstService;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminMstController extends Controller
{
    public function __construct(
        protected AdminMstService $adminMst
    ) {}

    /**
     * AdminMst list
     */
    public function list(ListAdminMstRequest $request): JsonResource
    {
        return $this->adminMst->list($request->all());
    }

    /**
     * Store admin mst
     */
    public function store(StoreAdminMstRequest $request): int
    {
        return $this->adminMst->store($request->all());
    }

    /**
     * Update admin mst
     */
    public function update(UpdateAdminMstRequest $request, string $id): int
    {
        $payload = $request->all();
        $payload['id'] = $id;

        return $this->adminMst->update($payload);
    }

    /**
     * Delete admin mst
     */
    public function delete(DeleteAdminMstRequest $request): void
    {
        $this->adminMst->delete($request->all());
    }
}
