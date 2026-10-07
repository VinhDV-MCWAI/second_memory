<?php

declare(strict_types=1);

namespace App\Http\Controllers\History\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\History\Master\AdminMstHist\DeleteAdminMstHistRequest;
use App\Http\Requests\History\Master\AdminMstHist\ListAdminMstHistRequest;
use App\Http\Requests\History\Master\AdminMstHist\StoreAdminMstHistRequest;
use App\Http\Requests\History\Master\AdminMstHist\UpdateAdminMstHistRequest;
use App\Http\Resources\History\Master\AdminMstHistResource;
use App\Services\History\Master\AdminMstHistService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminMstHistController extends Controller
{
    public function __construct(
        protected AdminMstHistService $adminMstHist
    ) {}

    /**
     * AdminMstHist list
     */
    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, AdminMstHistResource>>
     */
    public function list(ListAdminMstHistRequest $request): AnonymousResourceCollection
    {
        return $this->adminMstHist->list($request->validated());
    }

    /**
     * Store admin mst hist
     */
    public function store(StoreAdminMstHistRequest $request): int
    {
        return $this->adminMstHist->store($request->validated());
    }

    /**
     * Update admin mst hist
     */
    public function update(UpdateAdminMstHistRequest $request, string $id): int
    {
        $payload = $request->validated();
        $payload['id'] = $id;

        return $this->adminMstHist->update($payload);
    }

    /**
     * Delete admin mst hist
     */
    public function delete(DeleteAdminMstHistRequest $request): void
    {
        $this->adminMstHist->delete($request->validated());
    }
}
