<?php

namespace App\Services\Master;

use App\Http\Resources\Master\AdminMstResource;
use App\Repositories\History\Master\AdminMstHistRepository;
use App\Repositories\Master\AdminMstRepository;
use App\Services\AuditedCrudService;

class AdminMstService extends AuditedCrudService
{
    protected string $resource = AdminMstResource::class;

    protected string $historyForeignKey = 'admin_mst_id';

    public function __construct(AdminMstRepository $adminMst, AdminMstHistRepository $adminMstHist)
    {
        parent::__construct($adminMst, $adminMstHist);
    }
}
