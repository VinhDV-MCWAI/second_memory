<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ledger;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ledger\Evidence\DeleteEvidenceRequest;
use App\Http\Requests\Ledger\Evidence\ListEvidenceRequest;
use App\Http\Requests\Ledger\Evidence\StoreEvidenceRequest;
use App\Http\Requests\Ledger\Evidence\UpdateEvidenceRequest;
use App\Http\Resources\Ledger\EvidenceResource;
use App\Services\Ledger\EvidenceService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EvidenceController extends Controller
{
    public function __construct(private readonly EvidenceService $evidence) {}

    /**
     * @return AnonymousResourceCollection<LengthAwarePaginator<int, EvidenceResource>>
     */
    public function list(ListEvidenceRequest $request): AnonymousResourceCollection
    {
        return $this->evidence->list($request->validated());
    }

    /**
     * Create an evidence link for one or more skills
     */
    public function store(StoreEvidenceRequest $request): int
    {
        return $this->evidence->store($request->validated());
    }

    /**
     * Update an evidence link (imported rows: only type and is_public)
     */
    public function update(UpdateEvidenceRequest $request, string $id): int
    {
        return $this->evidence->update([...$request->validated(), 'id' => $id]);
    }

    public function delete(DeleteEvidenceRequest $request): void
    {
        $this->evidence->delete($request->validated());
    }
}
