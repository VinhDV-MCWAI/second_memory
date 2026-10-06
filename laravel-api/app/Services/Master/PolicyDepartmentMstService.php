<?php

namespace App\Services\Master;

use App\Http\Resources\Master\PolicyDepartmentMstResource;
use App\Repositories\History\Master\PolicyDepartmentMstHistRepository;
use App\Repositories\Master\PolicyDepartmentMstRepository;
use App\Services\AuditedCrudService;

class PolicyDepartmentMstService extends AuditedCrudService
{
    protected string $resource = PolicyDepartmentMstResource::class;

    protected string $historyForeignKey = 'policy_department_mst_id';

    public function __construct(PolicyDepartmentMstRepository $policyDepartmentMst, PolicyDepartmentMstHistRepository $policyDepartmentMstHist)
    {
        parent::__construct($policyDepartmentMst, $policyDepartmentMstHist);
    }
}
