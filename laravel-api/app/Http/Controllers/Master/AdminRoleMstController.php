<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\AdminRoleMst\ListAdminRoleMstRequest;
use App\Http\Requests\Master\AdminRoleMst\UpdateAdminRoleMstRequest;
use App\Http\Resources\Master\AdminRoleMstResource;
use App\Services\Master\AdminRoleMstService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminRoleMstController extends Controller
{
    public function __construct(
        protected AdminRoleMstService $adminRoleMst
    ) {}

    /**
     * AdminRoleMst list
     */
    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, AdminRoleMstResource>>
     */
    public function list(ListAdminRoleMstRequest $request): AnonymousResourceCollection
    {
        return $this->adminRoleMst->list($request->validated());
    }

    /**
     * Update admin role mst
     */
    public function update(UpdateAdminRoleMstRequest $request): bool
    {
        return $this->adminRoleMst->update($request->validated());
    }
}
