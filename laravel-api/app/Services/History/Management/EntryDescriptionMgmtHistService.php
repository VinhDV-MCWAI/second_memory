<?php

declare(strict_types=1);

namespace App\Services\History\Management;

use App\Http\Resources\History\Management\EntryDescriptionMgmtHistResource;
use App\Repositories\History\Management\EntryDescriptionMgmtHistRepository;
use App\Services\CrudService;

class EntryDescriptionMgmtHistService extends CrudService
{
    protected string $resource = EntryDescriptionMgmtHistResource::class;

    public function __construct(EntryDescriptionMgmtHistRepository $entryDescriptionMgmtHist)
    {
        parent::__construct($entryDescriptionMgmtHist);
    }
}
