<?php

declare(strict_types=1);

namespace App\Http\Controllers\History\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\History\Master\RoleMstHist\DeleteRoleMstHistRequest;
use App\Http\Requests\History\Master\RoleMstHist\ListRoleMstHistRequest;
use App\Http\Requests\History\Master\RoleMstHist\StoreRoleMstHistRequest;
use App\Http\Requests\History\Master\RoleMstHist\UpdateRoleMstHistRequest;
use App\Http\Resources\History\Master\RoleMstHistResource;
use App\Services\History\Master\RoleMstHistService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoleMstHistController extends Controller
{
    public function __construct(
        protected RoleMstHistService $roleMstHist
    ) {}

    /**
     * RoleMstHist list
     */
    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, RoleMstHistResource>>
     */
    public function list(ListRoleMstHistRequest $request): AnonymousResourceCollection
    {
        return $this->roleMstHist->list($request->validated());
    }

    /**
     * Store role mst hist
     */
    public function store(StoreRoleMstHistRequest $request): int
    {
        return $this->roleMstHist->store($request->validated());
    }

    /**
     * Update role mst hist
     */
    public function update(UpdateRoleMstHistRequest $request, string $id): int
    {
        $payload = $request->validated();
        $payload['id'] = $id;

        return $this->roleMstHist->update($payload);
    }

    /**
     * Delete role mst hist
     */
    public function delete(DeleteRoleMstHistRequest $request): void
    {
        $this->roleMstHist->delete($request->validated());
    }
}
