<?php

declare(strict_types=1);

namespace App\Services\History\Management;

use App\Http\Resources\History\Management\SocialMgmtHistResource;
use App\Repositories\History\Management\SocialMgmtHistRepository;
use App\Services\CrudService;

class SocialMgmtHistService extends CrudService
{
    protected string $resource = SocialMgmtHistResource::class;

    public function __construct(SocialMgmtHistRepository $socialMgmtHist)
    {
        parent::__construct($socialMgmtHist);
    }
}
