<?php

declare(strict_types=1);

namespace App\Services\Master;

use App\Http\Resources\Master\DepartmentMstResource;
use App\Repositories\History\Master\DepartmentMstHistRepository;
use App\Repositories\Master\DepartmentMstRepository;
use App\Services\AuditedCrudService;

class DepartmentMstService extends AuditedCrudService
{
    protected string $resource = DepartmentMstResource::class;

    protected string $historyForeignKey = 'department_mst_id';

    public function __construct(DepartmentMstRepository $departmentMst, DepartmentMstHistRepository $departmentMstHist)
    {
        parent::__construct($departmentMst, $departmentMstHist);
    }
}
