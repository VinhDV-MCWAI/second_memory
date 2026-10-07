<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\PolicyDepartmentMst\DeletePolicyDepartmentMstRequest;
use App\Http\Requests\Master\PolicyDepartmentMst\ListPolicyDepartmentMstRequest;
use App\Http\Requests\Master\PolicyDepartmentMst\StorePolicyDepartmentMstRequest;
use App\Http\Requests\Master\PolicyDepartmentMst\UpdatePolicyDepartmentMstRequest;
use App\Http\Resources\Master\PolicyDepartmentMstResource;
use App\Services\Master\PolicyDepartmentMstService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PolicyDepartmentMstController extends Controller
{
    public function __construct(
        protected PolicyDepartmentMstService $policyDepartmentMst
    ) {}

    /**
     * PolicyDepartmentMst list
     */
    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, PolicyDepartmentMstResource>>
     */
    public function list(ListPolicyDepartmentMstRequest $request): AnonymousResourceCollection
    {
        return $this->policyDepartmentMst->list($request->validated());
    }

    /**
     * Store policy department mst
     */
    public function store(StorePolicyDepartmentMstRequest $request): int
    {
        return $this->policyDepartmentMst->store($request->validated());
    }

    /**
     * Update policy department mst
     */
    public function update(UpdatePolicyDepartmentMstRequest $request, string $id): int
    {
        $payload = $request->validated();
        $payload['id'] = $id;

        return $this->policyDepartmentMst->update($payload);
    }

    /**
     * Delete policy department mst
     */
    public function delete(DeletePolicyDepartmentMstRequest $request): void
    {
        $this->policyDepartmentMst->delete($request->validated());
    }
}
