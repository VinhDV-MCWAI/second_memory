<?php

declare(strict_types=1);

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\EntryDescriptionMgmt\DeleteEntryDescriptionMgmtRequest;
use App\Http\Requests\Management\EntryDescriptionMgmt\ListEntryDescriptionMgmtRequest;
use App\Http\Requests\Management\EntryDescriptionMgmt\StoreEntryDescriptionMgmtRequest;
use App\Http\Requests\Management\EntryDescriptionMgmt\UpdateEntryDescriptionMgmtRequest;
use App\Services\Management\EntryDescriptionMgmtService;
use Illuminate\Http\Resources\Json\JsonResource;

class EntryDescriptionMgmtController extends Controller
{
    public function __construct(
        protected EntryDescriptionMgmtService $entryDescriptionMgmt
    ) {}

    /**
     * EntryDescriptionMgmt list
     */
    public function list(ListEntryDescriptionMgmtRequest $request): JsonResource
    {
        return $this->entryDescriptionMgmt->list($request->validated());
    }

    /**
     * Store entry description mgmt
     */
    public function store(StoreEntryDescriptionMgmtRequest $request): int
    {
        return $this->entryDescriptionMgmt->store($request->validated());
    }

    /**
     * Update entry description mgmt
     */
    public function update(UpdateEntryDescriptionMgmtRequest $request, string $id): int
    {
        $payload = $request->validated();
        $payload['id'] = $id;

        return $this->entryDescriptionMgmt->update($payload);
    }

    /**
     * Delete entry description mgmt
     */
    public function delete(DeleteEntryDescriptionMgmtRequest $request): void
    {
        $this->entryDescriptionMgmt->delete($request->validated());
    }
}
