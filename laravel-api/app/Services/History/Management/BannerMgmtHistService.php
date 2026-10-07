<?php

declare(strict_types=1);

namespace App\Services\History\Management;

use App\Http\Resources\History\Management\BannerMgmtHistResource;
use App\Repositories\History\Management\BannerMgmtHistRepository;
use App\Services\CrudService;

class BannerMgmtHistService extends CrudService
{
    protected string $resource = BannerMgmtHistResource::class;

    public function __construct(BannerMgmtHistRepository $bannerMgmtHist)
    {
        parent::__construct($bannerMgmtHist);
    }
}
