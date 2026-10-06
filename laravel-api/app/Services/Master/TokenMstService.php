<?php

declare(strict_types=1);

namespace App\Services\Master;

use App\Http\Resources\Master\TokenMstResource;
use App\Repositories\Master\TokenMstRepository;
use App\Services\CrudService;

class TokenMstService extends CrudService
{
    protected string $resource = TokenMstResource::class;

    public function __construct(TokenMstRepository $tokenMst)
    {
        parent::__construct($tokenMst);
    }
}
