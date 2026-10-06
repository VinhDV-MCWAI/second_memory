<?php

declare(strict_types=1);

namespace App\Services\History\Master;

use App\Http\Resources\History\Master\ApiMstHistResource;
use App\Repositories\History\Master\ApiMstHistRepository;
use App\Services\CrudService;

class ApiMstHistService extends CrudService
{
    protected string $resource = ApiMstHistResource::class;

    public function __construct(ApiMstHistRepository $apiMstHist)
    {
        parent::__construct($apiMstHist);
    }
}
