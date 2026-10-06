<?php

namespace App\Services\History\Management;

use App\Http\Resources\History\Management\UserMgmtHistResource;
use App\Repositories\History\Management\UserMgmtHistRepository;
use App\Services\CrudService;

class UserMgmtHistService extends CrudService
{
    protected string $resource = UserMgmtHistResource::class;

    public function __construct(UserMgmtHistRepository $userMgmtHist)
    {
        parent::__construct($userMgmtHist);
    }
}
