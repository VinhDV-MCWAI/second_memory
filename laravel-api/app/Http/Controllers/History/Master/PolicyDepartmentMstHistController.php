<?php

namespace App\Http\Controllers\History\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\History\Master\PolicyDepartmentMstHist\DeletePolicyDepartmentMstHistRequest;
use App\Http\Requests\History\Master\PolicyDepartmentMstHist\ListPolicyDepartmentMstHistRequest;
use App\Http\Requests\History\Master\PolicyDepartmentMstHist\StorePolicyDepartmentMstHistRequest;
use App\Http\Requests\History\Master\PolicyDepartmentMstHist\UpdatePolicyDepartmentMstHistRequest;
use App\Services\History\Master\PolicyDepartmentMstHistService;
use Illuminate\Http\Resources\Json\JsonResource;

class PolicyDepartmentMstHistController extends Controller
{
    public function __construct(
        protected PolicyDepartmentMstHistService $policyDepartmentMstHist
    ) {}

    /**
     * PolicyDepartmentMstHist list
     */
    public function list(ListPolicyDepartmentMstHistRequest $request): JsonResource
    {
        return $this->policyDepartmentMstHist->list($request->validated());
    }

    /**
     * Store policy department mst hist
     */
    public function store(StorePolicyDepartmentMstHistRequest $request): int
    {
        return $this->policyDepartmentMstHist->store($request->validated());
    }

    /**
     * Update policy department mst hist
     */
    public function update(UpdatePolicyDepartmentMstHistRequest $request, string $id): int
    {
        $payload = $request->validated();
        $payload['id'] = $id;

        return $this->policyDepartmentMstHist->update($payload);
    }

    /**
     * Delete policy department mst hist
     */
    public function delete(DeletePolicyDepartmentMstHistRequest $request): void
    {
        $this->policyDepartmentMstHist->delete($request->validated());
    }
}
