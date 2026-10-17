<?php

declare(strict_types=1);

namespace App\Services\Master;

use App\Constants\EndUserConst;
use App\Http\Resources\Master\EndUserResource;
use App\Repositories\Master\EndUserRepository;
use App\Services\AuditedCrudService;
use App\Services\AuditLogger;

/**
 * End users in the admin screens (ADR-0020): list, lock / unlock, reset the password, delete.
 * A locked or deleted end user is signed out on the next request (guard provider `active-end-users`).
 */
class EndUserService extends AuditedCrudService
{
    protected string $resource = EndUserResource::class;

    protected string $auditableType = EndUserConst::AUDITABLE_TYPE;

    public function __construct(EndUserRepository $endUsers, AuditLogger $auditLogger)
    {
        parent::__construct($endUsers, $auditLogger);
    }
}
