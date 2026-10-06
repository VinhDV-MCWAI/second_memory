<?php

namespace App\Services\Management;

use App\Http\Resources\Management\EntryDescriptionMgmtResource;
use App\Repositories\History\Management\EntryDescriptionMgmtHistRepository;
use App\Repositories\Management\EntryDescriptionMgmtRepository;
use App\Services\AuditedCrudService;

class EntryDescriptionMgmtService extends AuditedCrudService
{
    protected string $resource = EntryDescriptionMgmtResource::class;

    protected string $historyForeignKey = 'entry_description_mgmt_id';

    public function __construct(EntryDescriptionMgmtRepository $entryDescriptionMgmt, EntryDescriptionMgmtHistRepository $entryDescriptionMgmtHist)
    {
        parent::__construct($entryDescriptionMgmt, $entryDescriptionMgmtHist);
    }
}
