<?php

declare(strict_types=1);

namespace App\Http\Controllers\History\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\History\Management\EntryDescriptionMgmtHist\DeleteEntryDescriptionMgmtHistRequest;
use App\Http\Requests\History\Management\EntryDescriptionMgmtHist\ListEntryDescriptionMgmtHistRequest;
use App\Http\Requests\History\Management\EntryDescriptionMgmtHist\StoreEntryDescriptionMgmtHistRequest;
use App\Http\Requests\History\Management\EntryDescriptionMgmtHist\UpdateEntryDescriptionMgmtHistRequest;
use App\Http\Resources\History\Management\EntryDescriptionMgmtHistResource;
use App\Services\History\Management\EntryDescriptionMgmtHistService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EntryDescriptionMgmtHistController extends Controller
{
    public function __construct(
        protected EntryDescriptionMgmtHistService $entryDescriptionMgmtHist
    ) {}

    /**
     * EntryDescriptionMgmtHist list
     */
    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, EntryDescriptionMgmtHistResource>>
     */
    public function list(ListEntryDescriptionMgmtHistRequest $request): AnonymousResourceCollection
    {
        return $this->entryDescriptionMgmtHist->list($request->validated());
    }

    /**
     * Store entry description mgmt hist
     */
    public function store(StoreEntryDescriptionMgmtHistRequest $request): int
    {
        return $this->entryDescriptionMgmtHist->store($request->validated());
    }

    /**
     * Update entry description mgmt hist
     */
    public function update(UpdateEntryDescriptionMgmtHistRequest $request, string $id): int
    {
        $payload = $request->validated();
        $payload['id'] = $id;

        return $this->entryDescriptionMgmtHist->update($payload);
    }

    /**
     * Delete entry description mgmt hist
     */
    public function delete(DeleteEntryDescriptionMgmtHistRequest $request): void
    {
        $this->entryDescriptionMgmtHist->delete($request->validated());
    }
}
