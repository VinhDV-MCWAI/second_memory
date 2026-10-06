<?php

namespace App\Services\Management;

use App\Http\Resources\Management\UserMgmtResource;
use App\Repositories\History\Management\UserMgmtHistRepository;
use App\Repositories\Management\UserMgmtRepository;
use App\Services\AuditedCrudService;

class UserMgmtService extends AuditedCrudService
{
    protected string $resource = UserMgmtResource::class;

    protected string $historyForeignKey = 'user_mgmt_id';

    public function __construct(UserMgmtRepository $userMgmt, UserMgmtHistRepository $userMgmtHist)
    {
        parent::__construct($userMgmt, $userMgmtHist);
    }
}
