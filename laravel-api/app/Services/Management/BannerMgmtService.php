<?php

declare(strict_types=1);

namespace App\Services\Management;

use App\Http\Resources\Management\BannerMgmtResource;
use App\Repositories\History\Management\BannerMgmtHistRepository;
use App\Repositories\Management\BannerMgmtRepository;
use App\Services\AuditedCrudService;

class BannerMgmtService extends AuditedCrudService
{
    protected string $resource = BannerMgmtResource::class;

    protected string $historyForeignKey = 'banner_mgmt_id';

    public function __construct(BannerMgmtRepository $bannerMgmt, BannerMgmtHistRepository $bannerMgmtHist)
    {
        parent::__construct($bannerMgmt, $bannerMgmtHist);
    }
}
