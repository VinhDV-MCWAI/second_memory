<?php

namespace App\Http\Controllers\History\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\History\Master\AdminMstHist\DeleteAdminMstHistRequest;
use App\Http\Requests\History\Master\AdminMstHist\ListAdminMstHistRequest;
use App\Http\Requests\History\Master\AdminMstHist\StoreAdminMstHistRequest;
use App\Http\Requests\History\Master\AdminMstHist\UpdateAdminMstHistRequest;
use App\Services\History\Master\AdminMstHistService;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminMstHistController extends Controller
{
    public function __construct(
        protected AdminMstHistService $adminMstHist
    ) {}

    /**
     * AdminMstHist list
     */
    public function list(ListAdminMstHistRequest $request): JsonResource
    {
        return $this->adminMstHist->list($request->all());
    }

    /**
     * Store admin mst hist
     */
    public function store(StoreAdminMstHistRequest $request): int
    {
        return $this->adminMstHist->store($request->all());
    }

    /**
     * Update admin mst hist
     */
    public function update(UpdateAdminMstHistRequest $request, string $id): int
    {
        $payload = $request->all();
        $payload['id'] = $id;

        return $this->adminMstHist->update($payload);
    }

    /**
     * Delete admin mst hist
     */
    public function delete(DeleteAdminMstHistRequest $request): void
    {
        $this->adminMstHist->delete($request->all());
    }
}
