<?php

declare(strict_types=1);

namespace App\Services\History\Master;

use App\Http\Resources\History\Master\DepartmentMstHistResource;
use App\Repositories\History\Master\DepartmentMstHistRepository;
use App\Services\CrudService;

class DepartmentMstHistService extends CrudService
{
    protected string $resource = DepartmentMstHistResource::class;

    public function __construct(DepartmentMstHistRepository $departmentMstHist)
    {
        parent::__construct($departmentMstHist);
    }
}
