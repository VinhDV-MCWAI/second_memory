<?php

declare(strict_types=1);

namespace App\Http\Controllers\History\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\History\Management\EntryMgmtHist\DeleteEntryMgmtHistRequest;
use App\Http\Requests\History\Management\EntryMgmtHist\ListEntryMgmtHistRequest;
use App\Http\Requests\History\Management\EntryMgmtHist\StoreEntryMgmtHistRequest;
use App\Http\Requests\History\Management\EntryMgmtHist\UpdateEntryMgmtHistRequest;
use App\Http\Resources\History\Management\EntryMgmtHistResource;
use App\Services\History\Management\EntryMgmtHistService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EntryMgmtHistController extends Controller
{
    public function __construct(
        protected EntryMgmtHistService $entryMgmtHist
    ) {}

    /**
     * EntryMgmtHist list
     */
    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, EntryMgmtHistResource>>
     */
    public function list(ListEntryMgmtHistRequest $request): AnonymousResourceCollection
    {
        return $this->entryMgmtHist->list($request->validated());
    }

    /**
     * Store entry mgmt hist
     */
    public function store(StoreEntryMgmtHistRequest $request): int
    {
        return $this->entryMgmtHist->store($request->validated());
    }

    /**
     * Update entry mgmt hist
     */
    public function update(UpdateEntryMgmtHistRequest $request, string $id): int
    {
        $payload = $request->validated();
        $payload['id'] = $id;

        return $this->entryMgmtHist->update($payload);
    }

    /**
     * Delete entry mgmt hist
     */
    public function delete(DeleteEntryMgmtHistRequest $request): void
    {
        $this->entryMgmtHist->delete($request->validated());
    }
}
