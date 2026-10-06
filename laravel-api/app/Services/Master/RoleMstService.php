<?php

declare(strict_types=1);

namespace App\Services\Master;

use App\Http\Resources\Master\RoleMstResource;
use App\Repositories\History\Master\RoleMstHistRepository;
use App\Repositories\Master\RoleMstRepository;
use App\Services\AuditedCrudService;

class RoleMstService extends AuditedCrudService
{
    protected string $resource = RoleMstResource::class;

    protected string $historyForeignKey = 'role_mst_id';

    public function __construct(RoleMstRepository $roleMst, RoleMstHistRepository $roleMstHist)
    {
        parent::__construct($roleMst, $roleMstHist);
    }
}
