<?php

namespace App\Services\History\Master;

use App\Http\Resources\History\Master\AdminMstHistResource;
use App\Repositories\History\Master\AdminMstHistRepository;
use App\Services\CrudService;

class AdminMstHistService extends CrudService
{
    protected string $resource = AdminMstHistResource::class;

    public function __construct(AdminMstHistRepository $adminMstHist)
    {
        parent::__construct($adminMstHist);
    }
}
