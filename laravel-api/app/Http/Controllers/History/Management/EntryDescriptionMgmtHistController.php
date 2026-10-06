<?php

namespace App\Http\Controllers\History\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\History\Management\EntryDescriptionMgmtHist\DeleteEntryDescriptionMgmtHistRequest;
use App\Http\Requests\History\Management\EntryDescriptionMgmtHist\ListEntryDescriptionMgmtHistRequest;
use App\Http\Requests\History\Management\EntryDescriptionMgmtHist\StoreEntryDescriptionMgmtHistRequest;
use App\Http\Requests\History\Management\EntryDescriptionMgmtHist\UpdateEntryDescriptionMgmtHistRequest;
use App\Services\History\Management\EntryDescriptionMgmtHistService;
use Illuminate\Http\Resources\Json\JsonResource;

class EntryDescriptionMgmtHistController extends Controller
{
    public function __construct(
        protected EntryDescriptionMgmtHistService $entryDescriptionMgmtHist
    ) {}

    /**
     * EntryDescriptionMgmtHist list
     */
    public function list(ListEntryDescriptionMgmtHistRequest $request): JsonResource
    {
        return $this->entryDescriptionMgmtHist->list($request->all());
    }

    /**
     * Store entry description mgmt hist
     */
    public function store(StoreEntryDescriptionMgmtHistRequest $request): int
    {
        return $this->entryDescriptionMgmtHist->store($request->all());
    }

    /**
     * Update entry description mgmt hist
     */
    public function update(UpdateEntryDescriptionMgmtHistRequest $request, string $id): int
    {
        $payload = $request->all();
        $payload['id'] = $id;

        return $this->entryDescriptionMgmtHist->update($payload);
    }

    /**
     * Delete entry description mgmt hist
     */
    public function delete(DeleteEntryDescriptionMgmtHistRequest $request): void
    {
        $this->entryDescriptionMgmtHist->delete($request->all());
    }
}
