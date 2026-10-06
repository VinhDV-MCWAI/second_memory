<?php

declare(strict_types=1);

namespace App\Services\Master;

use App\Http\Resources\Master\ApiMstResource;
use App\Repositories\History\Master\ApiMstHistRepository;
use App\Repositories\Master\ApiMstRepository;
use App\Services\AuditedCrudService;

class ApiMstService extends AuditedCrudService
{
    protected string $resource = ApiMstResource::class;

    protected string $historyForeignKey = 'api_mst_id';

    public function __construct(ApiMstRepository $apiMst, ApiMstHistRepository $apiMstHist)
    {
        parent::__construct($apiMst, $apiMstHist);
    }
}
