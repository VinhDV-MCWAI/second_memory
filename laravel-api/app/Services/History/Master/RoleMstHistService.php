<?php

namespace App\Services\History\Master;

use App\Http\Resources\History\Master\RoleMstHistResource;
use App\Repositories\History\Master\RoleMstHistRepository;
use App\Services\CrudService;

class RoleMstHistService extends CrudService
{
    protected string $resource = RoleMstHistResource::class;

    public function __construct(RoleMstHistRepository $roleMstHist)
    {
        parent::__construct($roleMstHist);
    }
}
