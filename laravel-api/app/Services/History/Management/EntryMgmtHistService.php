<?php

declare(strict_types=1);

namespace App\Services\History\Management;

use App\Http\Resources\History\Management\EntryMgmtHistResource;
use App\Repositories\History\Management\EntryMgmtHistRepository;
use App\Services\CrudService;

class EntryMgmtHistService extends CrudService
{
    protected string $resource = EntryMgmtHistResource::class;

    public function __construct(EntryMgmtHistRepository $entryMgmtHist)
    {
        parent::__construct($entryMgmtHist);
    }
}
