<?php

declare(strict_types=1);

namespace App\Services\Management;

use App\Http\Resources\Management\EntryMgmtResource;
use App\Repositories\History\Management\EntryMgmtHistRepository;
use App\Repositories\Management\EntryMgmtRepository;
use App\Services\AuditedCrudService;

class EntryMgmtService extends AuditedCrudService
{
    protected string $resource = EntryMgmtResource::class;

    protected string $historyForeignKey = 'entry_mgmt_id';

    public function __construct(EntryMgmtRepository $entryMgmt, EntryMgmtHistRepository $entryMgmtHist)
    {
        parent::__construct($entryMgmt, $entryMgmtHist);
    }
}
