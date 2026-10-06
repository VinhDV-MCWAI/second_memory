<?php

namespace App\Services\Management;

use App\Http\Resources\Management\SocialMgmtResource;
use App\Repositories\History\Management\SocialMgmtHistRepository;
use App\Repositories\Management\SocialMgmtRepository;
use App\Services\AuditedCrudService;

class SocialMgmtService extends AuditedCrudService
{
    protected string $resource = SocialMgmtResource::class;

    protected string $historyForeignKey = 'social_mgmt_id';

    public function __construct(SocialMgmtRepository $socialMgmt, SocialMgmtHistRepository $socialMgmtHist)
    {
        parent::__construct($socialMgmt, $socialMgmtHist);
    }
}
