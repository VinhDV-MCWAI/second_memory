<?php

declare(strict_types=1);

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\EntryMgmt\DeleteEntryMgmtRequest;
use App\Http\Requests\Management\EntryMgmt\ListEntryMgmtRequest;
use App\Http\Requests\Management\EntryMgmt\StoreEntryMgmtRequest;
use App\Http\Requests\Management\EntryMgmt\UpdateEntryMgmtRequest;
use App\Services\Management\EntryMgmtService;
use Illuminate\Http\Resources\Json\JsonResource;

class EntryMgmtController extends Controller
{
    public function __construct(
        protected EntryMgmtService $entryMgmt
    ) {}

    /**
     * EntryMgmt list
     */
    public function list(ListEntryMgmtRequest $request): JsonResource
    {
        return $this->entryMgmt->list($request->validated());
    }

    /**
     * Store entry mgmt
     */
    public function store(StoreEntryMgmtRequest $request): int
    {
        return $this->entryMgmt->store($request->validated());
    }

    /**
     * Update entry mgmt
     */
    public function update(UpdateEntryMgmtRequest $request, string $id): int
    {
        $payload = $request->validated();
        $payload['id'] = $id;

        return $this->entryMgmt->update($payload);
    }

    /**
     * Delete entry mgmt
     */
    public function delete(DeleteEntryMgmtRequest $request): void
    {
        $this->entryMgmt->delete($request->validated());
    }
}
