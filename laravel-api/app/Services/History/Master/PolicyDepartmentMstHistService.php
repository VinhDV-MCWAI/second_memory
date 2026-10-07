<?php

declare(strict_types=1);

namespace App\Services\History\Master;

use App\Http\Resources\History\Master\PolicyDepartmentMstHistResource;
use App\Repositories\History\Master\PolicyDepartmentMstHistRepository;
use App\Services\CrudService;

class PolicyDepartmentMstHistService extends CrudService
{
    protected string $resource = PolicyDepartmentMstHistResource::class;

    public function __construct(PolicyDepartmentMstHistRepository $policyDepartmentMstHist)
    {
        parent::__construct($policyDepartmentMstHist);
    }
}
